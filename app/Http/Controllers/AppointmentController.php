<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientAppointmentResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\TreatmentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AppointmentController extends Controller
{
    // public function bookAppointment(Request $request, $id)
    // {
    //     $request->validate([
    //         'appointment_date' => 'required|date',
    //         'appointment_time' => 'required|date_format:H:i'
    //     ]);

    //     // Get doctor's schedule for that day
    //     $dayOfWeek = date('l', strtotime($request->appointment_date));
    //     $schedule = Schedule::where('doctor_id', $id)
    //         ->where('available_days', $dayOfWeek) // Direct match instead of FIND_IN_SET
    //         ->first();

    //     if (!$schedule) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Doctor is not available on this day.'
    //         ], 404);
    //     }

    //     // Ensure the slot is still available
    //     $exists = Appointment::where('doctor_id', $id)
    //         ->whereDate('appointment_date', $request->appointment_date)
    //         ->where('appointment_time', $request->appointment_time)
    //         ->where('status', '!=', 'cancelled')
    //         ->exists();

    //     if ($exists) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'This time slot is already booked.'
    //         ], 422);
    //     }

    //     // Create appointment
    //     Appointment::create([
    //         'doctor_id' => $id,
    //         'patient_id' => $request->user()->patient->id,
    //         'appointment_date' => $request->appointment_date,
    //         'appointment_time' => $request->appointment_time
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Appointment booked successfully.'
    //     ]);
    // }

    public function bookAppointment(Request $request, $id)
    {
        $request->validate([
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|date_format:H:i'
        ]);

        // Get doctor's schedule for that day
        $dayOfWeek = date('l', strtotime($request->appointment_date));
        $schedule = Schedule::where('doctor_id', $id)
            ->where('available_days', $dayOfWeek)
            ->first();

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doctor is not available on this day.'
            ], 404);
        }

        // Ensure the slot is still available
        $exists = Appointment::where('doctor_id', $id)
            ->whereDate('appointment_date', $request->appointment_date)
            ->where('appointment_time', $request->appointment_time)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'This time slot is already booked.'
            ], 422);
        }

        // Create Treatment Plan
        $treatmentPlan = TreatmentPlan::create([
            'doctor_id' => $id,
            'patient_id' => $request->user()->patient->id,
            'name' => 'Check Up', // Default name
            'status' => false, // Default status
            'date' => $request->appointment_date
        ]);

        // Create Appointment and link the Treatment Plan
        Appointment::create([
            'doctor_id' => $id,
            'patient_id' => $request->user()->patient->id,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'treatment_plan_id' => $treatmentPlan->id, // Link treatment plan
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment booked successfully, and treatment plan created.'
        ]);
    }


    public function getAvailableTimeSlots(Request $request, $doctorId)
    {
        // Get doctor's schedule for that day
        $dayOfWeek = date('l', strtotime($request->query('date')));

        $schedule = Schedule::where('doctor_id', $doctorId)
            ->where('available_days', $dayOfWeek) // Direct match instead of FIND_IN_SET
            ->first();

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doctor is not available on this day.'
            ], 404);
        }

        $startTime = strtotime($schedule->start_time);
        $endTime = strtotime($schedule->end_time);
        $slotDuration = 30 * 60; // 30 minutes per slot

        // Generate all possible time slots
        $availableSlots = [];
        for ($time = $startTime; $time < $endTime; $time += $slotDuration) {
            $availableSlots[] = date('H:i', $time);
        }

        // Fetch booked appointments for the given date
        $bookedSlots = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $request->query('date'))
            ->where('status', '!=', 'cancelled')
            ->pluck('appointment_time')
            ->map(function ($time) {
                return date('H:i', strtotime($time)); // Ensure format matches available slots
            })
            ->toArray();


        // Filter out booked slots
        $freeSlots = array_diff($availableSlots, $bookedSlots);

        return response()->json([
            'status' => 'success',
            'available_slots' => array_values($freeSlots),
        ]);
    }

    public function getAppointmentsForPatient($status)
    {
        $patientId = Auth::user()->patient->id; // Get the authenticated patient's ID

        $appointments = Appointment::where('patient_id', $patientId)
            ->where('status', $status) // Assuming 'status' column exists
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            $status . '_appointments' => PatientAppointmentResource::collection($appointments)
        ]);
    }

    public function getDoctorAppointments(Request $request)
    {
        $doctorId = $request->user()->doctor->id; // Get the authenticated doctor's ID
        $query = Appointment::where('doctor_id', $doctorId);

        // Filter by date if provided
        if ($request->has('date')) {
            $query->whereDate('appointment_date', $request->query('date'));
        }

        $appointments = $query
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'appointments' => AppointmentResource::collection($appointments)
        ]);
    }


    public function cancelAppointment(Request $request, $appointmentId)
    {
        $patientId = $request->user()->patient->id; // Get the authenticated patient ID

        // Find the appointment and ensure it belongs to the patient
        $appointment = Appointment::where('id', $appointmentId)
            ->where('patient_id', $patientId)
            ->where('status', 'pending') // Only allow canceling pending appointments
            ->first();

        if (!$appointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Appointment not found or cannot be cancelled.'
            ], 404);
        }

        // Update the appointment status to "canceled"
        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment cancelled successfully.'
        ]);
    }

    public function getDoctorAppointmentDetails(Request $request, $appointmentId)
    {
        $doctor = $request->user()->doctor; // Get authenticated doctor

        $appointment = Appointment::with(['patient.detail', 'treatmentPlan'])
            ->where('doctor_id', $doctor->id) // Ensure it's this doctor's appointment
            ->find($appointmentId);

        if (!$appointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Appointment not found or unauthorized access.'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'appointment' => [
                'id' => $appointment->id,
                'patient' => [
                    'id' => $appointment->patient->id,
                    'name' => $appointment->patient->detail->name ?? $appointment->patient->user->name,
                    'age' => $appointment->patient->detail->age ?? 'N/A',
                    'phone' => $appointment->patient->user->phone ?? 'N/A',
                    'email' => $appointment->patient->user->email ?? 'N/A',
                ],
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->appointment_time,
                'day' => $appointment->day,
                'estimate_duration' => '30 min',
                'status' => $appointment->status,
                'treatment_plan' => $appointment->treatmentPlan ? [
                    'id' => $appointment->treatmentPlan->id,
                    'name' => $appointment->treatmentPlan->name,
                    'status' => $appointment->treatmentPlan->status ? 'completed' : 'pending',
                    'date' => $appointment->treatmentPlan->date,
                ] : null,
            ]
        ]);
    }

    public function getDoctorAppointmentPaymentDetails(Request $request, $appointmentId)
    {
        $doctor = $request->user()->doctor; // Get authenticated doctor

        $appointment = Appointment
            ::where('doctor_id', $doctor->id) // Ensure it's this doctor's appointment
            ->find($appointmentId);

        if (!$appointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Appointment not found or unauthorized access.'
            ], 403);
        }
        $refference = $appointment->id . '00001234';
        return response()->json([
            'status' => 'success',
            'appointments' => [
                'id' => $appointment->id,
                'recipent' => $appointment->doctor->user->name,
                'refference' => $refference,
                'payment_method' => 'mastercard'
            ]
        ]);
    }

    // Profile
    public function getDoctorPatientUpcomingAppointments(Request $request, $id)
    {
        $doctor = $request->user()->doctor; // Get authenticated doctor

        $patient = Patient::whereHas('appointments', function ($q) use ($doctor, $id) {
            $q->where('doctor_id', $doctor->id)
                ->where('patient_id', $id);
        })->first();

        if (!$patient) {
            return response()->json([
                'status' => 'error',
                'message' => 'Patient not found or unauthorized access.'
            ], 403);
        }
        $appointments = Appointment::where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();
        return response()->json([
            'status' => 'success',
            'patient' => $appointments->map(function ($appoint) {
                return [
                    'id' => $appoint->patient->id,
                    'day' => $appoint->day,
                    'status' => $appoint->status,
                    'treatment_name' => $appoint->treatmentplan->name,
                    'appointment_date' => $appoint->appointment_date,
                    'appointment_time' => $appoint->appointment_time,
                    'created_at' => $appoint->created_at->toDateTimeString(),
                    'updated_at' => $appoint->updated_at->toDateTimeString(),
                ];
            })
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\TreatmentPlan;
use Illuminate\Http\Request;

class TreatmentPlanController extends Controller
{
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'appointment_id' => 'required|exists:appointments,id',
    //         'name' => 'required|string|max:255',
    //         'date' => 'required|date',
    //         'status' => 'boolean'
    //     ]);

    //     $treatmentPlan = TreatmentPlan::create($request->all());

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Treatment plan created successfully.',
    //         'treatment_plan' => $treatmentPlan
    //     ], 201);
    // }
    // Doctor Route
    public function getPatientTreatmentPlans(Request $request, $patientId)
    {
        $doctorId = $request->user()->doctor->id; // Get authenticated doctor's ID

        // Fetch the treatment plans for the given patient created by this doctor
        $treatmentPlans = TreatmentPlan::where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->orderBy('date', 'desc') // Sort by date (latest first)
            ->get(['id', 'name', 'status', 'date']);

        // If no treatment plans found, return an error
        if ($treatmentPlans->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No treatment plans found for this patient.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'treatment_plans' => $treatmentPlans,
        ]);
    }
    // Doctor Route
    public function createTreatmentPlan(Request $request, $patientId)
    {
        $doctorId = $request->user()->doctor->id; // Get authenticated doctor's ID

        // Validate request
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'status' => 'boolean',
        ]);

        // Find the first appointment time for this patient with this doctor
        $firstAppointment = Appointment::where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->first();

        if (!$firstAppointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'No previous appointment found for this patient.',
            ], 422);
        }

        // Get the first appointment's time
        $appointmentTime = $firstAppointment->appointment_time;

        // Check if the requested appointment time is available on this date
        $isSlotTaken = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $request->date)
            ->where('appointment_time', $appointmentTime)
            ->where('status', '!=', 'cancelled') // Ignore cancelled appointments
            ->exists();

        if ($isSlotTaken) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected appointment time is already booked on this date.',
            ], 422);
        }

        // Create the treatment plan
        $treatmentPlan = TreatmentPlan::create([
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'name' => $request->name,
            'date' => $request->date,
            'status' => $request->status ?? false, // Default status to false if not provided
        ]);


        // Create a new appointment with the same time as the first appointment
        $appointment = Appointment::create([
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'appointment_date' => $request->date, // Use treatment plan date
            'appointment_time' => $appointmentTime, // Use first appointment's time
            'status' => 'pending', // Default to pending
            'treatment_plan_id' => $treatmentPlan->id, // Associate with the treatment plan
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Treatment plan and appointment created successfully.',
            'treatment_plan' => $treatmentPlan,
            'appointment' => $appointment
        ], 201);
    }

    public function deleteTreatmentPlan(Request $request, $treatmentPlanId)
    {
        $doctor = $request->user()->doctor; // Get the authenticated doctor

        // Find the treatment plan and ensure the doctor is authorized
        $treatmentPlan = TreatmentPlan::where('id', $treatmentPlanId)
            ->where('doctor_id', $doctor->id) // Ensure the doctor owns it
            ->first();

        if (!$treatmentPlan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Treatment plan not found or unauthorized access.'
            ], 404);
        }

        // Delete the treatment plan
        $treatmentPlan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Treatment plan deleted successfully.'
        ]);
    }

    public function updateTreatmentPlan(Request $request, $id)
    {
        $doctor = $request->user()->doctor; // Get authenticated doctor

        // Validate request data (all fields are optional)
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'status' => 'sometimes|boolean',
        ]);

        // Find the treatment plan and ensure it belongs to the authenticated doctor
        $treatmentPlan = TreatmentPlan::where('doctor_id', $doctor->id)->find($id);

        if (!$treatmentPlan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Treatment plan not found or unauthorized access.',
            ], 404);
        }

        // Update only the provided fields
        if ($request->has('name')) {
            $treatmentPlan->name = $request->name;
        }
        if ($request->has('date')) {
            $treatmentPlan->date = $request->date;

            // Update the related appointment date if it exists
            $appointment = Appointment::where('treatment_plan_id', $treatmentPlan->id)->first();
            if ($appointment) {
                $appointment->appointment_date = $request->date;
                $appointment->save();
            }
        }
        if ($request->has('status')) {
            $treatmentPlan->status = $request->status;
        }

        // Save the updated treatment plan
        $treatmentPlan->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Treatment plan updated successfully.',
            'treatment_plan' => $treatmentPlan
        ]);
    }

    // Patient Route
    public function getAuthPatientTreatmentPlans(Request $request)
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or no patient record found.',
            ], 403);
        }

        $treatmentPlans = $patient->treatmentPlans()->with('doctor.user')->latest()->get();

        return response()->json([
            'status' => 'success',
            'treatment_plans' => $treatmentPlans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'status' => $plan->status ? 'Completed' : 'Pending',
                    'date' => $plan->date,
                    'doctor' => [
                        'id' => $plan->doctor->id,
                        'name' => 'Dr. ' . $plan->doctor->user->name,
                    ],
                ];
            }),
        ]);
    }
}

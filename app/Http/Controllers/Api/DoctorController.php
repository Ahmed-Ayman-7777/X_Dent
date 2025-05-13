<?php

namespace App\Http\Controllers\Api;

use App\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\DoctorAppointmentBookingDetailResource;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\PatientListResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DoctorController extends Controller
{
    use ApiResponseTrait;
    public function me(Request $request)
    {
        $user = $request->user();
        // $doctor = $user->doctor;
        return response()->json([
            'doctor' => $user,
        ]);
    }
    public function userSpecialization(Request $request)
    {
        $user = $request->user();
        $user = User::with('doctor.specializations')->find($user->id);
        $doctor = $user->doctor;
        return response()->json([
            'doctor' => $user,
        ]);
    }
    public function getAllDoctors()
    {
        $users = User::where('role', 'doctor')->get(); // doctors
        return $this->successResponse(DoctorResource::collection($users));
    }
    public function getAllPatients()
    {
        $users = User::where('role', 'patient')->get(); // patients
        return $this->successResponse(PatientResource::collection($users));
    }

    public function filter(Request $request)
    {
        $filters = $request->only(['review_rating', 'min_price', 'max_price', 'specialization_names']);

        // Use the filter method from the Doctor model
        $doctors = Doctor::filter($filters)->get();

        $users = [];
        foreach ($doctors as $doctor) {
            $user = $doctor->user;
            $users[] = $user;
        }

        return $this->successResponse(DoctorResource::collection($users));
    }

    public function doctorDetailsForBooking($id)
    {
        $doctor = Doctor::findOrFail($id);

        return $this->successResponse(new DoctorAppointmentBookingDetailResource($doctor->user));
    }


    // Homepage
    public function index(Request $request)
    {
        $doctorId = $request->user()->doctor->id; // Get authenticated doctor ID

        // Get total number of appointments
        $totalAppointments = Appointment::where('doctor_id', $doctorId)->count();

        // Get count of appointments by status
        $pendingAppointmentsCount = Appointment::where('doctor_id', $doctorId)->where('status', 'pending')->count();
        $canceledAppointmentsCount = Appointment::where('doctor_id', $doctorId)->where('status', 'cancelled')->count();
        $completedAppointmentsCount = Appointment::where('doctor_id', $doctorId)->where('status', 'confirmed')->count();

        // Get all pending appointments
        $pendingAppointments = Appointment::where('doctor_id', $doctorId)
            ->where('status', 'pending')
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'total_appointments' => $totalAppointments,
            'pending_appointments_count' => $pendingAppointmentsCount,
            'canceled_appointments_count' => $canceledAppointmentsCount,
            'completed_appointments_count' => $completedAppointmentsCount,
            'pending_appointments' => AppointmentResource::collection($pendingAppointments)
        ]);
    }

    public function getDoctorPatients(Request $request)
    {
        $doctorId = $request->user()->doctor->id; // Get authenticated doctor’s ID

        // Fetch unique patients who have had at least one appointment with the doctor
        $patients = Patient::whereHas('appointments', function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId);
        })->with([
            'detail:id,patient_id,name', // Get name from patient_details
            'appointments' => function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId)
                    ->orderBy('appointment_date', 'asc') // Get first appointment
                    ->select('id', 'patient_id', 'appointment_date') // Optimize selection
                    ->limit(1);
            }
        ])->get();

        return response()->json([
            'status' => 'success',
            'patients' => $patients->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'name' => $patient->detail->name ?? $patient->user->name, // Get name from patient_details
                    'apppointment_date' => $patient->appointments->first()->appointment_date ?? null, // Get last appointment
                ];
            }),
        ]);
    }

    public function getPatientById(Request $request, $patientId)
    {
        $doctorId = $request->user()->doctor->id; // Get authenticated doctor's ID

        // Fetch the patient along with details and appointments for this doctor
        $patient = Patient::where('id', $patientId)
            ->whereHas('appointments', function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId);
            })
            ->with([
                'detail:id,patient_id,name,age', // Fetch patient details
                'appointments' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId)
                        ->orderBy('appointment_date', 'asc') // Fetch all appointments in order
                        ->select('id', 'doctor_id', 'patient_id', 'appointment_date', 'appointment_time', 'status');
                }
            ])
            ->first();

        // If the patient does not exist or has no appointments with the doctor, return an error
        if (!$patient) {
            return response()->json([
                'status' => 'error',
                'message' => 'Patient not found or has no appointments with this doctor.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->detail->name ?? $patient->user->name, // Get name from patient_details
                'age' => $patient->detail->age ?? 'N/A',
                'phone' => $patient->user->phone ?? 'Not Provided',
                'email' => $patient->user->email ?? 'Not Provided',
                'first_appointment_date' => optional($patient->appointments->first())->appointment_date,
                // 'appointments' => $patient->appointments->map(function ($appointment) {
                //     return [
                //         'id' => $appointment->id,
                //         'date' => $appointment->appointment_date,
                //         'time' => $appointment->appointment_time,
                //         'status' => $appointment->status,
                //     ];
                // }),
            ],
        ]);
    }

    public function runCommand()
    {
        Artisan::call('db:seed');

        return response()->json([
            'status' => 'success',
            'message' => 'Database seeding executed successfully.',
        ]);
    }
}

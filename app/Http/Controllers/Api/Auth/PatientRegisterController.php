<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRegisterRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use App\ApiResponseTrait; // Import the trait

class PatientRegisterController extends Controller
{
    use ApiResponseTrait;

    public function register(PatientRegisterRequest $request)
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'patient',
        ]);

        $patient = Patient::create([
            'user_id' => $user->id,
            //'description' => $request->description,
        ]);

        // $patient = User::with('patient')->find($user->id);

        $result = array_merge($patient->user->toArray(), [
            'patient_id' => $patient->id,
        ]);

        return $this->successResponse($result, 'Patient registered successfully', 201);
    }
}

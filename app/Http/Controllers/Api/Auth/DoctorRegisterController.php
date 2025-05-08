<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorRegisterRequest;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\ApiResponseTrait; // Import the trait
use App\Models\Specialization;

class DoctorRegisterController extends Controller
{
    use ApiResponseTrait;

    public function register(DoctorRegisterRequest $request)
    {
        // return response()->json(['message' => $request->input('myspec')]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'doctor',
        ]);

        // if ($request->hasFile('cv_file')) {
        //     $cvFilePath = $request->file('cv_file')->store('cv_files', 'public'); // Stores in storage/app/public/cv_files
        // } else {
        //     $cvFilePath = null;
        // }

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'degree' => $request->degree,
            'university' => $request->university,
            'year_graduated' => $request->year_graduated,
            'location' => $request->location,
            'price' => $request->price,
            // 'cv_file' => null, // Save file path in the database
        ]);


        // Check if specializations are provided in the request
        if ($request->has('myspec')) {

            $specialization_names = $request->post('myspec');

            if (strpos($specialization_names, ',') !== false) {
                $specialization_names = explode(',', $specialization_names);
            } else {
                // If it's a single name, make it an array with one element
                $specialization_names = [$specialization_names];
            }

            // $all_specializations = explode(',', $request->input('myspec'));
            $specialization_ids = [];

            foreach ($specialization_names as $specialization) {
                $_specialization = Specialization::firstOrCreate([
                    'name' => $specialization,
                ]);

                $specialization_ids[] = $_specialization->id;
            }

            $doctor->specializations()->sync($specialization_ids);  // Using sync to update the relationship
        }

        // $user = User::with('doctor')->find($user->id);

        $result = array_merge($doctor->user->toArray(), [
            'doctor_id' => $doctor->id,
            'specializations' => $doctor->specializations->pluck('name')->toArray(),
        ]);

        return $this->successResponse($result, 'Doctor registered successfully', 201);
    }
}

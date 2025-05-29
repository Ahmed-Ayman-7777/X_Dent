<?php

namespace App\Http\Controllers;

use App\ApiResponseTrait;
use App\Models\Doctor;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpecializationController extends Controller
{
    use ApiResponseTrait;

    public function getAllSpecialization()
    {
        $specializations = Specialization::all();
        $specializations_names = $specializations->pluck('name');
        return $this->successResponse($specializations_names);
    }

    public function getDoctorBySpecializationName(Request $request)
    {

        // Get the specialization name(s) from the request
        $specialization_names = $request->name;

        // If the input is a comma-separated list, split it into an array
        if (strpos($specialization_names, ',') !== false) {
            $specialization_names = explode(',', $specialization_names);
        } else {
            // If it's a single name, make it an array with one element
            $specialization_names = [$specialization_names];
        }

        // Initialize an array to store specialization IDs and missing specializations
        $specialization_ids = [];
        $missing_specializations = [];

        // Iterate over each specialization name and find it in the database
        foreach ($specialization_names as $specialization_name) {
            // Trim any extra spaces around the name
            // $specialization_name = trim($specialization_name);

            // Try to find the specialization in the database
            $_specialization = Specialization::where('name', $specialization_name)->first();

            if ($_specialization) {
                // If the specialization exists, add its ID to the array
                $specialization_ids[] = $_specialization->id;
            } else {
                // If the specialization does not exist, add it to the missing list
                $missing_specializations[] = $specialization_name;
            }
        }

        // If there are any missing specializations, return a message for them
        if (count($missing_specializations) > 0) {
            return $this->errorResponse([
                'message' => 'The following specializations do not exist: ' . implode(', ', $missing_specializations)
            ], 404);
        }

        // Retrieve doctors associated with the found specializations
        $doctors = Doctor::whereHas('specializations', function ($query) use ($specialization_ids) {
            $query->whereIn('specializations.id', $specialization_ids);
        })->get();

        $result = $doctors->map(function ($doctor) use ($doctors) {
            return array_merge($doctor->user->toArray(), [
                'specializations' => $doctor->specializations->pluck('name')->toArray(),
            ]);
        });

        // Return the list of doctors with their associated specializations
        return $this->successResponse($result);
    }

    public function createSpecializationsList(Request $request)
    {

        $request->validate([
            'specializations' => 'required|array',
            'specializations.*' => 'required|string|max:255',
        ]);

        $created = [];

        foreach ($request->specializations as $name) {
            $created[] = Specialization::firstOrCreate(['name' => $name]);
        }

        return response()->json([
            'message' => 'Specializations created successfully',
            'data' => $created,
        ], 201);
    }
}

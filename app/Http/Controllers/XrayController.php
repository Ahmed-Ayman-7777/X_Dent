<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Xray;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class XrayController extends Controller
{
    public function uploadXray(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json(['message' => 'Unauthorized or not a patient.'], 403);
        }

        $imagePath = $request->file('image')->store('xrays', 'public');

        $xray = Xray::create([
            'patient_id' => $patient->id,
            'image_path' => $imagePath,
        ]);

        return response()->json([
            'message' => 'X-ray uploaded successfully.',
            'xray' => $xray,
        ]);
    }

    public function getAuthPatientXrays(Request $request)
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or not a patient.',
            ], 403);
        }

        $xrays = $patient->xrays()->latest()->get();

        return response()->json([
            'status' => 'success',
            'xrays' => $xrays->map(function ($xray) {
                return [
                    'id' => $xray->id,
                    'image_url' => asset('storage/' . $xray->image_path),
                    'uploaded_at' => $xray->created_at->toDateString(),
                ];
            }),
        ]);
    }

    public function reuploadXrayImage(Request $request, $id)
    {
        // Allow for 5 minutes of execution time
        set_time_limit(300);

        $xray = Xray::find($id);

        if (!$xray || !Storage::disk('public')->exists($xray->image_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'X-ray or image not found.'
            ], 404);
        }

        // Get the file from storage
        $imagePath = storage_path('app/public/' . $xray->image_path);

        // if (is_file($imagePath)) {
        //     return response()->json([
        //         'success' => 'true'
        //     ]);
        // }

        // Send image to upload route (e.g., /api/xray/upload)
        $response = Http::timeout(300)  // Timeout after 60 seconds for each attempt
            ->attach(
                'file',
                file_get_contents($imagePath),
                basename($imagePath)
            )->post('https://dental-xray-yolov11-api.onrender.com/detect');

        // Check if the response is successful
        if ($response->successful()) {
            // Get the image data (raw binary)
            $imageData = $response->body();

            // Get the original image extension (you may want to use the same as input)
            $extension = 'jpg';  // Default extension if not returned by API, change as needed.

            // Generate a unique name for the image
            $imageName = 'xray_' . time() . '.' . $extension;

            // Store the image in the 'xrays' folder in the 'public' disk
            $imagePath = 'xrays/' . $imageName;
            Storage::disk('public')->put($imagePath, $imageData);

            // Generate the URL for the stored image
            $imageUrl = asset('storage/' . $imagePath);

            return response()->json([
                'status' => 'success',
                'message' => 'X-ray image re-uploaded successfully.',
                'image_url' => $imageUrl,  // URL to access the image
                'image_name' => $imageName,  // Image name with extension
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to re-upload X-ray image.',
                'api_error' => $response->body()  // API error message
            ], $response->status());
        }
    }
    public function showXrayById(Request $request, $id)
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or not a patient',
            ], 403);
        }

        $xray = Xray::where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();

        if (!$xray) {
            return response()->json([
                'status' => 'error',
                'message' => 'X-ray not found or access denied',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'xray' => [
                'id' => $xray->id,
                'image_url' => asset('storage/' . $xray->image_path),
                'uploaded_at' => $xray->created_at->toDateString(),
            ]
        ]);
    }
    // Doctor
    public function getDoctorPatientXrays(Request $request, $id)
    {
        $user = $request->user();

        // Optional: Check if the authenticated user is a doctor
        if (!$user->doctor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only doctors can access patient X-rays.'
            ], 403);
        }

        // Fetch all X-rays for the given patient
        $patient = Patient::where('id', $id)->first();
        $xrays = $patient->xrays()->latest()->get();

        if (!$xrays) {
            return response()->json([
                'status' => 'error',
                'message' => 'there is no xrays for this patient',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $xrays->map(function ($xray) {
                return [
                    'id' => $xray->id,
                    'image_url' => asset('storage/' . $xray->image_path),
                    'uploaded_at' => $xray->created_at->toDateTimeString(),
                ];
            }),
        ]);
    }

    public function showXrayByIdForDoctor(Request $request, $id)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or not a doctor',
            ], 403);
        }

        $xray = Xray::where('id', $id)
            ->first();

        if (!$xray) {
            return response()->json([
                'status' => 'error',
                'message' => 'X-ray not found',
            ], 404);
        }

        $patient_id = $xray->patient->id;
        $patient = $doctor->appointments()
            ->whereHas('patient', function ($q) use ($patient_id) {
                $q->where('id', $patient_id);
            })->first();

        if (!$patient) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access Denied',
            ], 403);
        }
        return response()->json([
            'status' => 'success',
            'xray' => [
                'id' => $xray->id,
                'image_url' => asset('storage/' . $xray->image_path),
                'uploaded_at' => $xray->created_at->toDateString(),
            ]
        ]);
    }
}

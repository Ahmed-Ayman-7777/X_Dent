<?php

namespace App\Http\Controllers;

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

    public function reuploadXrayImage($id)
    {
        $xray = Xray::find($id);

        if (!$xray || !Storage::disk('public')->exists($xray->image_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'X-ray or image not found.'
            ], 404);
        }

        // Get the file from storage
        $imagePath = storage_path('app/public/' . $xray->image_path);

        if (is_file($imagePath)) {
            return response()->json([
                'success' => 'true'
            ]);
        }

        // Send image to upload route (e.g., /api/xray/upload)
        // $response = Http::attach(
        //     'image',
        //     file_get_contents($imagePath),
        //     basename($imagePath)
        // )->post('http://127.0.0.1:8000/api/xray/upload');

        return response()->json([
            'status' => 'forwarded',
        ]);
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
}

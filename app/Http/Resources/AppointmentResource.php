<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            //'patient' => $this->patient->user->name,
            //'doctor_name' => $this->doctor->user->name,
            //'phone' => $this->doctor->phone,
            'day' => $this->day,
            'status' => $this->status,
            'appointment_date' => $this->appointment_date,
            'appointment_time' => $this->appointment_time,
            'patient' => $this->patient->detail ?
                new PatientDetailResource($this->patient->detail) :
                [
                    'id' => $this->patient->id ?? 'this is not the id of patient',
                    'name' => $this->patient->detail->name ?? $this->patient->user->name,
                    'age' => $this->patient->detail->age ?? 'N/A',
                    'phone' => $this->patient->user->phone ?? 'N/A',
                    'message' => $this->patient->detail->message ?? 'N/A',
                ], //new PatientResource($this->patient->user), // Include patient details
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}

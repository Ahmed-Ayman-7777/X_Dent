<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientDetailResource extends JsonResource
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
            //'patient_id' => $this->patient_id,
            'name' => $this->name ?? $this->patient->user->name,
            'age' => $this->age,
            'phone' => $this->patient->user->phone,
            'message' => $this->message,
            // 'image' => $this->image ? asset('storage/' . $this->image) : null, // Full image URL
            // 'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}

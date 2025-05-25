<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->doctor->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'price' => $this->doctor->price,
            'reviews_count' => $this->doctor->reviews_count,
            'average_rating' => $this->doctor->average_rating,
            'role' => $this->role,
            'degree' => $this->doctor->degree,
            'university' => $this->doctor->university,
            'year_graduated' => $this->doctor->year_graduated,
            'location' => $this->doctor->location,
            'cv_file' => $this->doctor->cv_file,
            'specialization' => $this->doctor->specializations->pluck('name')->toArray(),
            'schedules' => $this->doctor->schedules,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // 'doctor' => new DoctorProfileResource($this->doctor), // Include the doctor's profile details using DoctorProfileResource
        ];
    }
}

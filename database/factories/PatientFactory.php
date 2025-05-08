<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition()
    {
        return [
            //'description' => $this->faker->paragraph, // Random description text
            'user_id' => User::factory()->create([
                'role' => 'patient'
            ]), // Generate a random user_id using the User factory
        ];
    }
}

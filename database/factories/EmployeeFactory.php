<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'nik' => $this->faker->numerify('################'),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'position' => $this->faker->randomElement(['Manager', 'Kasir', 'Cook', 'Server', 'Admin']),
            'department' => $this->faker->randomElement(['Operasional', 'Dapur', 'Administrasi']),
            'join_date' => $this->faker->date(),
            'employment_type' => $this->faker->randomElement(['Tetap', 'Kontrak', 'Harian']),
            'status' => 'active',
            'basic_salary' => $this->faker->numberBetween(3000000, 10000000),
            'bank_name' => $this->faker->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI']),
            'bank_account' => $this->faker->numerify('##########'),
        ];
    }
}

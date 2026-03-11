<?php

namespace Database\Factories;

use App\Models\Alumni;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumniFactory extends Factory
{
    protected $model = Alumni::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'graduation_year' => $this->faker->numberBetween(2000, date('Y')),
            'testimonial' => $this->faker->paragraph(),
            'image_url' => null,
            'content_type' => 'text',
            'is_featured' => false,
            'is_published' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}

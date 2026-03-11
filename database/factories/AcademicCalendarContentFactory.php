<?php

namespace Database\Factories;

use App\Models\AcademicCalendarContent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicCalendarContentFactory extends Factory
{
    protected $model = AcademicCalendarContent::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'calendar_image_url' => $this->faker->imageUrl(),
            'semester' => $this->faker->randomElement([1, 2]),
            'academic_year_start' => 2024,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}

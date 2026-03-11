<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'category' => $this->faker->word(),
            'icon_name' => $this->faker->word(),
            'image_url' => null,
            'color_class' => 'bg-blue-500',
            'description' => $this->faker->sentence(),
            'link' => null,
            'is_featured' => false,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}

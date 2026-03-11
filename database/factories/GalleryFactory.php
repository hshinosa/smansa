<?php

namespace Database\Factories;

use App\Models\Gallery;
use Illuminate\Database\Eloquent\Factories\Factory;

class GalleryFactory extends Factory
{
    protected $model = Gallery::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['photo', 'video']),
            'url' => null,
            'thumbnail_url' => null,
            'is_external' => false,
            'category' => $this->faker->word(),
            'tags' => [],
            'date' => $this->faker->date(),
            'is_featured' => false,
        ];
    }
}

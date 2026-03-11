<?php

namespace Database\Factories;

use App\Models\Seragam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seragam>
 */
class SeragamFactory extends Factory
{
    protected $model = Seragam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Seragam Putih-Abu',
                'Seragam Batik',
                'Seragam Olah Raga',
                'Seragam Khusus',
                'Seragam Ekstrakurikuler',
            ]),
            'slug' => $this->faker->unique()->slug(2),
            'category' => $this->faker->randomElement(['Harian', 'Khusus', 'Ekstrakurikuler']),
            'description' => $this->faker->paragraph(),
            'usage_days' => $this->faker->randomElements(
                ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                $this->faker->numberBetween(1, 5)
            ),
            'rules' => $this->faker->optional()->paragraph(),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the seragam is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set category to Harian.
     */
    public function harian(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Harian',
        ]);
    }

    /**
     * Set category to Khusus.
     */
    public function khusus(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Khusus',
        ]);
    }

    /**
     * Set category to Ekstrakurikuler.
     */
    public function ekstrakurikuler(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Ekstrakurikuler',
        ]);
    }
}

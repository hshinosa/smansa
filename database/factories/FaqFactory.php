<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence(),
            'answer' => $this->faker->paragraph(),
            'category' => $this->faker->word(),
            'is_published' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}

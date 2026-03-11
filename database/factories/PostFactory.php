<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $admin = Admin::first() ?? Admin::factory()->create();
        
        return [
            'title' => $this->faker->sentence(),
            'excerpt' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'featured_image' => null,
            'category' => $this->faker->word(),
            'slug' => $this->faker->slug(),
            'status' => $this->faker->randomElement(['draft', 'published']),
            'is_featured' => false,
            'published_at' => null,
            'author_id' => $admin->id,
        ];
    }
}

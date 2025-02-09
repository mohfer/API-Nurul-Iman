<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\News>
 */
class NewsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isPublished = $this->faker->boolean();

        return [
            'title' => $this->faker->sentence(),
            'image_url' => 'https://placehold.co/800x600/png',
            'image_name' => $this->faker->word(),
            'content' => $this->faker->paragraph(10),
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'is_published' => $isPublished,
            'published_at' => $isPublished ? $this->faker->dateTimeBetween('-1 year', 'now') : null,
        ];
    }
}

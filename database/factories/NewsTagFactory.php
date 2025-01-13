<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsTag>
 */
class NewsTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'news_id' => News::factory()->create()->id,
            'tag_id' => Tag::factory()->create()->id
        ];
    }
}

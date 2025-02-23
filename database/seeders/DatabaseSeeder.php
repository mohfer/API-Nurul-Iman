<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\News;
use App\Models\User;
use App\Models\NewsTag;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        activity()->disableLogging();

        $this->call([
            CategorySeeder::class,
            TagSeeder::class,
        ]);

        $tags = Tag::all();
        $categories = Category::all();

        User::factory()->count(5)->create()->each(function ($user) use ($tags, $categories) {
            News::factory()->count(3)->create([
                'user_id' => $user->id,
                'category_id' => $categories->random()->id,
            ])->each(function ($news) use ($tags) {
                $news->tags()->attach($tags->random(3));
            });
        });

        $this->call([
            PermissionSeeder::class,
            AgendaSeeder::class,
            FacilitySeeder::class,
            AnnouncementSeeder::class,
            GallerySeeder::class,
        ]);

        // News::factory(10)->create();
        // NewsTag::factory(10)->create();
        // User::factory(10)->create();
        // Category::factory(10)->create();

        activity()->enableLogging();
    }
}

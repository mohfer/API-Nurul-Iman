<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\News;
use App\Models\NewsTag;
use App\Models\User;
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
            PermissionSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
            TagSeeder::class,
        ]);

        // News::factory(10)->create();
        // NewsTag::factory(10)->create();
        // User::factory(10)->create();
        // Category::factory(10)->create();

        activity()->enableLogging();
    }
}

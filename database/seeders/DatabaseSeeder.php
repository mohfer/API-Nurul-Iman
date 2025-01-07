<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\TagSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PermissionSeeder;

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
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class
        ]);

        activity()->enableLogging();
    }
}

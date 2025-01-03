<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'user',
            'category',
            'news',
            'news-tag',
            'tag',
            'gallery',
            'role'
        ];

        $roles = [
            'Super Admin',
            'Admin',
            'Writer'
        ];

        $actions = [
            'create',
            'read',
            'update',
            'delete'
        ];

        foreach ($permissions as $permission) {
            foreach ($actions as $action) {
                Permission::create(['name' => $permission . '-' . $action]);
            }
        }

        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }

        $superAdmin = Role::findByName('Super Admin');
        $admin = Role::findByName('Admin');
        $writer = Role::findByName('Writer');

        $superAdmin->givePermissionTo(Permission::all());
        $adminPermissions = Permission::whereNotIn('name', ['role-create', 'role-read', 'role-update', 'role-delete'])->get();
        $admin->givePermissionTo($adminPermissions);
        $writer->givePermissionTo('news-create', 'news-read', 'news-update', 'news-delete');

        User::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'email' => 'super@admin.com',
            'password' => bcrypt('password')
        ])->assignRole('Super Admin');
    }
}

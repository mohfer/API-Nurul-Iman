<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
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
            'role',
            'permission'
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

        $customPermissions = [
            'category',
            'news',
            'user',
            'tag',
            'gallery',
        ];

        $customActions = [
            'trashed',
            'restore',
            'forceDelete'
        ];

        foreach ($permissions as $permission) {
            foreach ($actions as $action) {
                Permission::create(['name' => $permission . '.' . $action]);
            }
        }

        foreach ($customPermissions as $customPermission) {
            foreach ($customActions as $customAction) {
                Permission::create(['name' => $customPermission . '.' . $customAction]);
            }
        }

        Permission::create(['name' => 'log.read']);

        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }

        $superAdmin = Role::findByName('Super Admin');
        $admin = Role::findByName('Admin');
        $writer = Role::findByName('Writer');

        $superAdmin->givePermissionTo(Permission::all());
        $adminPermissions = Permission::whereNotIn('name', [
            'role.create',
            'role.read',
            'role.update',
            'role.delete',
            'log.read'
        ])->get();
        $admin->givePermissionTo($adminPermissions);
        $writer->givePermissionTo('news.create', 'news.read', 'news.update', 'news.delete');

        User::create([
            'name' => 'Super Admin',
            'email' => 'super@admin.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ])->assignRole('Super Admin');
    }
}

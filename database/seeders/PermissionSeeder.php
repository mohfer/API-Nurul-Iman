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
            'agenda',
            'announcement',
            'news',
            'news-tag',
            'tag',
            'gallery',
            'facility',
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
            'agenda',
            'announcement',
            'news',
            'user',
            'tag',
            'gallery',
            'facility'
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

        Permission::create(['name' => 'dashboard.read']);
        Permission::create(['name' => 'dashboard.superAdmin']);
        Permission::create(['name' => 'dashboard.admin']);
        Permission::create(['name' => 'dashboard.writer']);
        Permission::create(['name' => 'log.read']);

        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }

        $superAdmin = Role::findByName('Super Admin');
        $admin = Role::findByName('Admin');
        $writer = Role::findByName('Writer');

        $superAdmin->syncPermissions(Permission::all());

        $adminPermissions = Permission::whereNotIn('name', [
            'dashboard.superAdmin',
            'dashboard.writer',
            'role.create',
            'role.read',
            'role.update',
            'role.delete',
            'user.update',
            'user.delete',
            'user.trashed',
            'user.restore',
            'user.forceDelete',
            'category.forceDelete',
            'news.forceDelete',
            'tag.forceDelete',
            'gallery.forceDelete',
            'agenda.forceDelete',
            'facility.forceDelete',
            'announcement.forceDelete',
            'log.read'
        ])->get();
        $admin->syncPermissions($adminPermissions);

        $writer->syncPermissions(['dashboard.read', 'dashboard.writer', 'news.create', 'news.read', 'news.update', 'news.delete']);

        User::create([
            'name' => 'Super Admin',
            'email' => 'super@admin.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ])->assignRole('Super Admin');

        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ])->assignRole('Admin');

        User::create([
            'name' => 'Writer',
            'email' => 'writer@admin.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ])->assignRole('Writer');
    }
}

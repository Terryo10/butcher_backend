<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Create admin role
        $adminRole = Role::create(['name' => 'admin']);

        // Create driver role
        $driverRole = Role::create(['name' => 'driver']);

        // Create customer role for regular users
        $customerRole = Role::create(['name' => 'customer']);

        // Create driver permissions
        $driverPermissions = [
            'view_deliveries',
            'update_delivery_status',
            'view_own_delivery_history',
            'assign_self_to_delivery',
            'unassign_self_from_delivery',
            'access_driver_panel'
        ];

        foreach ($driverPermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create admin permissions
        $adminPermissions = [
            'view_all_deliveries',
            'assign_deliveries',
            'unassign_deliveries',
            'manage_drivers',
            'review_driver_applications',
            'access_admin_panel'
        ];

        foreach ($adminPermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to driver role
        $driverRole->syncPermissions($driverPermissions);

        // Assign all permissions to admin role
        $adminRole->syncPermissions(array_merge($driverPermissions, $adminPermissions));

        // After creating roles, we'll assign all existing users to be customers by default
        // and then you can manually assign admin roles to specific users later
        try {
            $users = \App\Models\User::all();
            foreach ($users as $user) {
                $user->assignRole('customer');
            }

            // Optionally, if you know the email of your admin user, you can assign them the admin role
            // For example:
            // $adminUser = \App\Models\User::where('email', 'admin@example.com')->first();
            // if ($adminUser) {
            //     $adminUser->assignRole('admin');
            // }
        } catch (\Exception $e) {
            // Log the error but don't let it stop the migration
            \Illuminate\Support\Facades\Log::error('Error assigning initial roles: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        // Get all roles by name
        $roles = ['admin', 'driver', 'customer'];
        foreach ($roles as $roleName) {
            $role = Role::findByName($roleName);
            if ($role) {
                $role->delete();
            }
        }

        // Get all permissions
        $permissions = [
            'view_deliveries',
            'update_delivery_status',
            'view_own_delivery_history',
            'assign_self_to_delivery',
            'unassign_self_from_delivery',
            'access_driver_panel',
            'view_all_deliveries',
            'assign_deliveries',
            'unassign_deliveries',
            'manage_drivers',
            'review_driver_applications',
            'access_admin_panel'
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::findByName($permissionName);
            if ($permission) {
                $permission->delete();
            }
        }
    }
};

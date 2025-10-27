<?php

namespace Database\Seeders;

use App\Models\Permission as ModelsPermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Permission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sortMenu = ModelsPermission::firstOrCreate(['name' => 'sort menu', 'group' => 'Navigation']);
        $createMenu = ModelsPermission::firstOrCreate(['name' => 'create menu', 'group' => 'Navigation']);
        $editMenu = ModelsPermission::firstOrCreate(['name' => 'edit menu', 'group' => 'Navigation']);
        $deleteMenu = ModelsPermission::firstOrCreate(['name' => 'delete menu', 'group' => 'Navigation']);
        
        $createPermission = ModelsPermission::firstOrCreate(['name' => 'create permission', 'group' => 'Permission']);
        $editPermission = ModelsPermission::firstOrCreate(['name' => 'edit permission', 'group' => 'Permission']);
        $deletePermission = ModelsPermission::firstOrCreate(['name' => 'delete permission', 'group' => 'Permission']);
        
        $createRole = ModelsPermission::firstOrCreate(['name' => 'create role', 'group' => 'Role']);
        $editRole = ModelsPermission::firstOrCreate(['name' => 'edit role', 'group' => 'Role']);
        $deleteRole = ModelsPermission::firstOrCreate(['name' => 'delete role', 'group' => 'Role']);
        
        $createUser = ModelsPermission::firstOrCreate(['name' => 'create user', 'group' => 'User']);
        $editUser = ModelsPermission::firstOrCreate(['name' => 'edit user', 'group' => 'User']);
        $deleteUser = ModelsPermission::firstOrCreate(['name' => 'delete user', 'group' => 'User']);
        
        $allLeaveRequest = ModelsPermission::firstOrCreate(['name' => 'all leave request', 'group' => 'Leave Request']);
        $createForOthersLeaveRequest = ModelsPermission::firstOrCreate(['name' => 'create for others leave request', 'group' => 'Leave Request']);
        $deleteLeaveRequest = ModelsPermission::firstOrCreate(['name' => 'delete leave request', 'group' => 'Leave Request']);
        $createLeaveRequest = ModelsPermission::firstOrCreate(['name' => 'create leave request', 'group' => 'Leave Request']);
        $editLeaveRequest = ModelsPermission::firstOrCreate(['name' => 'edit leave request', 'group' => 'Leave Request']);
        $approveLeaveRequest = ModelsPermission::firstOrCreate(['name' => 'approve leave request', 'group' => 'Leave Request']);
    }
}

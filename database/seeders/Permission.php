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
        $createMenu = ModelsPermission::firstOrCreate(['name' => 'create menu', 'group' => 'Navigation']);
        $editMenu = ModelsPermission::firstOrCreate(['name' => 'edit menu', 'group' => 'Navigation']);
        $deleteMenu = ModelsPermission::firstOrCreate(['name' => 'delete menu', 'group' => 'Navigation']);
        
        $createPermission = ModelsPermission::firstOrCreate(['name' => 'create permission', 'group' => 'Permission']);
        $editPermission = ModelsPermission::firstOrCreate(['name' => 'edit permission', 'group' => 'Permission']);
        $deletePermission = ModelsPermission::firstOrCreate(['name' => 'delete permission', 'group' => 'Permission']);
    }
}

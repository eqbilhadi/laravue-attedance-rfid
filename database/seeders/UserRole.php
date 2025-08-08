<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserRole extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $developer = Role::firstOrCreate(['name' => 'developer', 'color' => 'bg-stone-500']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'color' => 'bg-sky-500']);
        $teacher = Role::firstOrCreate(['name' => 'teacher', 'color' => 'bg-amber-500']);

        $userDeveloper = User::firstOrCreate(
            ['email' => 'developer@example.com'],
            [
                'name' => fake()->name(),
                'password' => 'password',
                'username' => 'developer',
                'birthplace' => fake()->city(),
                'birthdate' => fake()->date(),
                'gender' => fake()->randomElement(['l', 'p']),
                'phone' => fake()->numerify('08##########'),
                'address' => fake()->address(),
            ]
        );

        $userAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => fake()->name(),
                'password' => 'password',
                'username' => 'admin',
                'birthplace' => fake()->city(),
                'birthdate' => fake()->date(),
                'gender' => fake()->randomElement(['l', 'p']),
                'phone' => fake()->numerify('08##########'),
                'address' => fake()->address(),
            ]
        );

        $userTeacher = User::firstOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => fake()->name(),
                'password' => 'password',
                'username' => 'teacher',
                'birthplace' => fake()->city(),
                'birthdate' => fake()->date(),
                'gender' => fake()->randomElement(['l', 'p']),
                'phone' => fake()->numerify('08##########'),
                'address' => fake()->address(),
            ]
        );

        $userDeveloper->assignRole($developer);
        $userAdmin->assignRole($admin);
        $userTeacher->assignRole($teacher);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Sample Dummy Users Array
        $users = [
            [
                'name'=>'User ini bisa Scan',
                'email'=>'user@gmail.com',
                'email_verified_at' => now(),
                'password'=> Hash::make('password'),
                'remember_token' => Str::random(10),
            ],
            [
                'name'=>'User ini Report',
                'email'=>'user2@gmail.com',
                'email_verified_at' => now(),
                'password'=> Hash::make('password'),
                'remember_token' => Str::random(10),
            ],
        ];

        foreach ($users as $key => $userData) {
            // Create the user
            $user = User::create($userData);

            // Assign roles based on user index
            if ($key === 0) { // First user gets both roles
                $user->roles()->attach([
                    Role::where('name', 'master-item')->first()->id,
                    Role::where('name', 'office')->first()->id,
                ]);
            } elseif ($key === 1) { // Second user gets only the office role
                $user->roles()->attach(Role::where('name', 'office')->first()->id);
            }
        }
    }
}
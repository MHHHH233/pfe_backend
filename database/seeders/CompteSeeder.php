<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompteSeeder extends Seeder
{
    public function run()
    {
        DB::table('compte')->insert([
            [
                'name' => 'Admin',
                'prenom' => 'Admin',
                'age' => '20',
                'email' => 'admin@example.com',
                'password' => Hash::make('Hashed_password3'),
                'role' => 'admin',
                'pfp' => 'pfp.png',
                'telephone' => '0606060606',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'User',
                'prenom' => 'User',
                'age' => '20',
                'email' => 'user@example.com',
                'password' => Hash::make('Hashed_password3'),
                'role' => 'user',
                'pfp' => 'pfp.png',
                'telephone' => '0606060606',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 
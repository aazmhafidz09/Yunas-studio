<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Super Admin',
            'username' => 'sayunas',
            'password' => Hash::make('sayunas'),
            'role_id' => 1,
            'is_active' => true
        ]);
        DB::table('users')->insert([
            'name' => 'Admin',
            'username' => 'ayunas',
            'password' => Hash::make('ayunas'),
            'role_id' => 2,
            'is_active' => true
        ]);
    }
}

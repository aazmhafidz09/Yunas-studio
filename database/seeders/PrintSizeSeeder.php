<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrintSizeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('print_sizes')->insert([
            ['name' => '4R'],
            ['name' => '8R + Frame'],
            ['name' => '8RP + Frame'],
            ['name' => '10R + Frame'],
            ['name' => '12RP + Frame'],
            ['name' => '16RP + Frame'],
            ['name' => '20RP + Frame'],
        ]);
    }
}
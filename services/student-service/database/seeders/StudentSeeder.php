<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Student::create([
            'full_name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'age' => 20,
        ]);

        Student::create([
            'full_name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'age' => 22,
        ]);

        Student::create([
            'full_name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'age' => 21,
        ]);

        Student::create([
            'full_name' => 'Diana Prince',
            'email' => 'diana@example.com',
            'age' => 23,
        ]);
    }
}

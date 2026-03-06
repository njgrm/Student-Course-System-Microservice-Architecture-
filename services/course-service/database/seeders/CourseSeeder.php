<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Course::create([
            'name' => 'Introduction to Computer Science',
            'description' => 'Fundamentals of programming and computational thinking.',
            'credits' => 3,
        ]);

        Course::create([
            'name' => 'Data Structures and Algorithms',
            'description' => 'Core data structures, sorting, and algorithmic design.',
            'credits' => 4,
        ]);

        Course::create([
            'name' => 'Web Development',
            'description' => 'Full-stack web development with modern frameworks.',
            'credits' => 3,
        ]);

        Course::create([
            'name' => 'Database Systems',
            'description' => 'Relational databases, SQL, and database design principles.',
            'credits' => 3,
        ]);
    }
}

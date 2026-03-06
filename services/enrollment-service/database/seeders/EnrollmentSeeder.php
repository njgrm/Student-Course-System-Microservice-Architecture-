<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Enrollment::create([
            'student_id' => 1,
            'course_id' => 1,
            'enrolled_at' => now(),
        ]);

        Enrollment::create([
            'student_id' => 1,
            'course_id' => 3,
            'enrolled_at' => now(),
        ]);

        Enrollment::create([
            'student_id' => 2,
            'course_id' => 2,
            'enrolled_at' => now(),
        ]);

        Enrollment::create([
            'student_id' => 3,
            'course_id' => 1,
            'enrolled_at' => now(),
        ]);

        Enrollment::create([
            'student_id' => 4,
            'course_id' => 4,
            'enrolled_at' => now(),
        ]);
    }
}

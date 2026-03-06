<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    protected $fillable = ['student_id', 'course_id', 'enrolled_at'];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'course_id' => 'integer',
            'enrolled_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'credits'];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
        ];
    }
}

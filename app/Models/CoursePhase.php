<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePhase extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'name', 'order'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}

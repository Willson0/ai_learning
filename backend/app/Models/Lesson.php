<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $guarded = false;

    public function course() {
        return $this->belongsTo(Course::class, "course_id");
    }
    public function userLessons () {
        return $this->hasMany(UserLesson::class, "lesson_id");
    }
}

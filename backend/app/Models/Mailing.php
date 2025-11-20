<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mailing extends Model
{
    protected $guarded = false;

    public function post () {
        return $this->belongsTo(Post::class);
    }
}

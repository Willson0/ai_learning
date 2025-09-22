<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $guarded = false;

    public function user() {
        return $this->belongsTo(User::class);
    }
    public function probe () {
        return $this->belongsTo(Probe::class);
    }
}

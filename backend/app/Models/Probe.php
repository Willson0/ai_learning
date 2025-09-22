<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Probe extends Model
{
    protected $guarded = false;

    public function variants () {
        return $this->hasMany(Variant::class);
    }
}

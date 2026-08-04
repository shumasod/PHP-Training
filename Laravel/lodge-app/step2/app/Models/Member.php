<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = ['name', 'rank', 'initiation_date'];

    public function lodge()
    {
        return $this->belongsTo(Lodge::class);
    }
}

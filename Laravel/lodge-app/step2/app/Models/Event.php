<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['title', 'description', 'date', 'type'];

    public function lodge()
    {
        return $this->belongsTo(Lodge::class);
    }
}

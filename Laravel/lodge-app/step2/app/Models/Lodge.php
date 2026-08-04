<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lodge extends Model
{
    protected $fillable = ['name', 'location', 'founded_year'];

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}

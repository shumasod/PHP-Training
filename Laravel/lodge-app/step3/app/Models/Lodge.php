<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lodge extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = ['name', 'location', 'founded_year'];
    
    protected $casts = [
        'founded_year' => 'integer',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
    
    // Upcoming events accessor
    public function getUpcomingEventsAttribute()
    {
        return $this->events()->where('date', '>=', now())->orderBy('date')->get();
    }
}

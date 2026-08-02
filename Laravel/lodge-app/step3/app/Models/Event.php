<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;
    
    // Constants for event types
    public const TYPES = [
        'Ritual',
        'Meeting',
        'Social'
    ];
    
    protected $fillable = ['title', 'description', 'date', 'type'];

    protected $casts = [
        'date' => 'date',
    ];

    public function lodge(): BelongsTo
    {
        return $this->belongsTo(Lodge::class);
    }
    
    // Scope for upcoming events
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now())->orderBy('date');
    }
}

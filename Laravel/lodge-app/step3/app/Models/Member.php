<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;
    
    // Constants for ranks
    public const RANKS = [
        'Entered Apprentice',
        'Fellow Craft',
        'Master Mason'
    ];
    
    protected $fillable = ['name', 'rank', 'initiation_date'];
    
    protected $casts = [
        'initiation_date' => 'date',
    ];

    public function lodge(): BelongsTo
    {
        return $this->belongsTo(Lodge::class);
    }
    
    // Promote the member to the next rank
    public function promote(): bool
    {
        $currentRankIndex = array_search($this->rank, self::RANKS);
        
        if ($currentRankIndex < count(self::RANKS) - 1) {
            $this->update(['rank' => self::RANKS[$currentRankIndex + 1]]);
            return true;
        }
        
        return false;
    }
}

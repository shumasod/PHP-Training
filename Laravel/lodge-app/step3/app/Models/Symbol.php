<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Symbol extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'description', 'meaning'];
}

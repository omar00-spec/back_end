<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Performance extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'technique',
        'tactique',
        'physique',
        'mental',
        'commentaire'
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
} 
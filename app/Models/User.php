<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'player_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    /**
     * Relation avec le modèle Player
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}

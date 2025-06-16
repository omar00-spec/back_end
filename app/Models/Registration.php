<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'player_firstname',
        'player_lastname',
        'birth_date',
        'category_id',
        'parent_name',
        'parent_email',
        'parent_phone',
        'address',
        'city',
        'player_phone',
        'player_email',
        'documents',
        'payment_method',
        'status',
        'payment_status',
        'payment_reference',
        'payment_justification'
    ];

    protected $casts = [
        'documents' => 'array',
        'birth_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relation avec le joueur
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}

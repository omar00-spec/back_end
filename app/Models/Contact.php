<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'childAge', 'message', 'read', 'response', 'responded_at'];
    
    protected $casts = [
        'read' => 'boolean',
        'responded_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'type', 'content', 'location', 'event_time', 'image', 'date', 'status'];

    /**
     * Vérifie si cette actualité est un événement
     */
    public function isEvent()
    {
        return $this->type === 'event';
    }
}

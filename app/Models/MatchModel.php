<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchModel extends Model
{
    use HasFactory;
    
    protected $table = 'matches_models';

    protected $fillable = [
        'category_id', 
        'date', 
        'time', 
        'opponent', 
        'location', 
        'result'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    /**
     * Détermine si le match est à venir ou déjà joué
     * 
     * @return bool
     */
    public function isUpcoming()
    {
        return is_null($this->result);
    }
}

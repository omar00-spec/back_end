<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = ['firstname', 'lastname', 'birth_date', 'photo', 'team', 'category_id', 'yellow_cards'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function registration()
    {
        return $this->hasOne(Registration::class);
    }
    
    public function performance()
    {
        return $this->hasOne(Performance::class);
    }
}

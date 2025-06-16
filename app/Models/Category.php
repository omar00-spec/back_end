<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'age_min',
        'age_max'
      ];

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function coaches()
    {
        return $this->hasMany(Coach::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function matches()
    {
        return $this->hasMany(MatchModel::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }
}

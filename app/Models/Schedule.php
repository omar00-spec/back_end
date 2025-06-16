<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'day', 'start_time', 'end_time', 'activity'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

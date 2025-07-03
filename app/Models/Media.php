<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 
        'type', 
        'title', 
        'description', 
        'file_path',
        'url',
        'public_id',
        'format',
        'size'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

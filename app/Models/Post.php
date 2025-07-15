<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'content',
        'weight',
        'creation_date',
        'supports',
        'media',
        'mentions',
        'categories',
        'comments',
    ];

    protected $casts = [
        'supports' => 'array',
        'media' => 'array',
        'mentions' => 'array',
        'categories' => 'array',
        'comments' => 'array',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterTemplate extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'file_path',
        'original_filename',
        'file_extension'
    ];
}

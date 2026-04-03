<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GraduationLetter extends Model
{
    protected $fillable = [
        'graduate_id',
        'letter_number',
        'issue_date',
        'content',
        'signed_by',
        'is_printed',
        'printed_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'is_printed' => 'boolean',
        'printed_at' => 'datetime',
    ];

    public function graduate(): BelongsTo
    {
        return $this->belongsTo(Graduate::class);
    }

    public function signedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public static function generateLetterNumber(): string
    {
        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
        $count = self::whereYear('created_at', $year)->count() + 1;
        $number = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "{$number}/SKL/SMK-YL/{$month}/{$year}";
    }
}

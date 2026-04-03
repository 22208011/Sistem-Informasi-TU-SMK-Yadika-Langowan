<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SumativeFinalHistory extends Model
{
    protected $fillable = [
        'sumative_final_id',
        'action',
        'performed_by',
        'old_status',
        'new_status',
        'notes',
    ];

    public function sumativeFinal(): BelongsTo
    {
        return $this->belongsTo(SumativeFinal::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getActionNameAttribute(): string
    {
        return match($this->action) {
            'created' => 'Dibuat',
            'submitted' => 'Diajukan',
            'verified' => 'Diverifikasi',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'updated' => 'Diperbarui',
            default => $this->action,
        };
    }
}

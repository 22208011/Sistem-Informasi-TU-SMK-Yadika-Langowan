<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public const ACTIONS = [
        'create' => 'Dibuat',
        'update' => 'Diperbarui',
        'delete' => 'Dihapus',
        'restore' => 'Dipulihkan',
        'login' => 'Login',
        'logout' => 'Logout',
        'export' => 'Diekspor',
        'import' => 'Diimpor',
        'approve' => 'Disetujui',
        'reject' => 'Ditolak',
        'print' => 'Dicetak',
        'view' => 'Dilihat',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo('auditable', 'model_type', 'model_id');
    }

    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = []
    ): self {
        $user = Auth::user();
        
        return self::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'model_type' => $model?->getMorphClass(),
            'model_id' => $model?->getKey(),
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
        ]);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByModel($query, string $modelClass)
    {
        return $query->where('model_type', 'like', '%' . $modelClass . '%');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function getActionNameAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function getModelNameAttribute(): string
    {
        if (!$this->model_type) {
            return '-';
        }

        $className = class_basename($this->model_type);
        
        return match($className) {
            'Student' => 'Siswa',
            'Employee' => 'Pegawai',
            'User' => 'Pengguna',
            'Classroom' => 'Kelas',
            'Subject' => 'Mata Pelajaran',
            'AcademicYear' => 'Tahun Pelajaran',
            'Department' => 'Jurusan',
            'Grade' => 'Nilai',
            'Exam' => 'Ujian',
            'SumativeFinal' => 'Nilai Sumatif',
            'Graduate' => 'Lulusan',
            'Letter' => 'Surat',
            'CallingLetter' => 'Surat Panggilan',
            'Extracurricular' => 'Ekstrakurikuler',
            default => $className,
        };
    }

    public function getChangedFieldsAttribute(): array
    {
        if ($this->action === 'create') {
            return array_keys($this->new_values ?? []);
        }

        if ($this->action === 'delete') {
            return array_keys($this->old_values ?? []);
        }

        $changed = [];
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $old[$key] !== $value) {
                $changed[] = $key;
            }
        }

        return $changed;
    }
}

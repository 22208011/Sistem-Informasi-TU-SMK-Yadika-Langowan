<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtracurricularMember extends Model
{
    protected $fillable = [
        'extracurricular_id',
        'student_id',
        'academic_year_id',
        'role',
        'join_date',
        'leave_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'join_date' => 'date',
        'leave_date' => 'date',
    ];

    public const ROLES = [
        'anggota' => 'Anggota',
        'ketua' => 'Ketua',
        'wakil_ketua' => 'Wakil Ketua',
        'sekretaris' => 'Sekretaris',
        'bendahara' => 'Bendahara',
    ];

    public const STATUSES = [
        'aktif' => 'Aktif',
        'tidak_aktif' => 'Tidak Aktif',
        'keluar' => 'Keluar',
    ];

    public function extracurricular(): BelongsTo
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
}

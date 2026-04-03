<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'priority',
        'target_audience',
        'target_department_id',
        'target_classroom_id',
        'author_id',
        'published_at',
        'expires_at',
        'is_pinned',
        'is_active',
        'attachment',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_pinned' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Announcement Type Constants
     */
    public const TYPE_GENERAL = 'general';
    public const TYPE_ACADEMIC = 'academic';
    public const TYPE_EVENT = 'event';
    public const TYPE_URGENT = 'urgent';
    public const TYPE_HOLIDAY = 'holiday';

    public const TYPES = [
        self::TYPE_GENERAL => 'Umum',
        self::TYPE_ACADEMIC => 'Akademik',
        self::TYPE_EVENT => 'Kegiatan',
        self::TYPE_URGENT => 'Penting/Mendesak',
        self::TYPE_HOLIDAY => 'Libur',
    ];

    public const TYPE_COLORS = [
        self::TYPE_GENERAL => 'zinc',
        self::TYPE_ACADEMIC => 'blue',
        self::TYPE_EVENT => 'green',
        self::TYPE_URGENT => 'red',
        self::TYPE_HOLIDAY => 'purple',
    ];

    /**
     * Priority Constants
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Rendah',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH => 'Tinggi',
    ];

    /**
     * Target Audience Constants
     */
    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_STUDENTS = 'students';
    public const AUDIENCE_TEACHERS = 'teachers';
    public const AUDIENCE_PARENTS = 'parents';
    public const AUDIENCE_STAFF = 'staff';
    public const AUDIENCE_SPECIFIC_CLASS = 'specific_class';
    public const AUDIENCE_SPECIFIC_DEPARTMENT = 'specific_department';

    public const AUDIENCES = [
        self::AUDIENCE_ALL => 'Semua',
        self::AUDIENCE_STUDENTS => 'Siswa',
        self::AUDIENCE_TEACHERS => 'Guru',
        self::AUDIENCE_PARENTS => 'Orang Tua',
        self::AUDIENCE_STAFF => 'Staf',
        self::AUDIENCE_SPECIFIC_CLASS => 'Kelas Tertentu',
        self::AUDIENCE_SPECIFIC_DEPARTMENT => 'Jurusan Tertentu',
    ];

    /**
     * Get the author
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get target department
     */
    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    /**
     * Get target classroom
     */
    public function targetClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'target_classroom_id');
    }

    /**
     * Scope for active announcements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for published announcements
     */
    public function scopePublished($query)
    {
                return $query->where(function ($q) {
                                                $q->whereNull('published_at')
                                                    ->orWhere('published_at', '<=', now());
                                         })
                                         ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope for pinned announcements
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope for target audience
     */
    public function scopeForAudience($query, string $audience)
    {
        return $query->where(function ($q) use ($audience) {
            $q->where('target_audience', self::AUDIENCE_ALL)
              ->orWhere('target_audience', $audience);
        });
    }

    /**
     * Scope announcements visible to a specific user based on role and profile.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('target_audience', self::AUDIENCE_ALL);

            if ($user->isStudent()) {
                $q->orWhere('target_audience', self::AUDIENCE_STUDENTS);

                if ($user->student) {
                    $q->orWhere(function (Builder $sq) use ($user) {
                        $sq->where('target_audience', self::AUDIENCE_SPECIFIC_CLASS)
                            ->where('target_classroom_id', $user->student->classroom_id);
                    });

                    $q->orWhere(function (Builder $sq) use ($user) {
                        $sq->where('target_audience', self::AUDIENCE_SPECIFIC_DEPARTMENT)
                            ->where('target_department_id', $user->student->department_id);
                    });
                }
            }

            if ($user->isParent()) {
                $q->orWhere('target_audience', self::AUDIENCE_PARENTS);

                $student = $user->guardian?->student;
                if ($student) {
                    $q->orWhere(function (Builder $sq) use ($student) {
                        $sq->where('target_audience', self::AUDIENCE_SPECIFIC_CLASS)
                            ->where('target_classroom_id', $student->classroom_id);
                    });

                    $q->orWhere(function (Builder $sq) use ($student) {
                        $sq->where('target_audience', self::AUDIENCE_SPECIFIC_DEPARTMENT)
                            ->where('target_department_id', $student->department_id);
                    });
                }
            }

            if ($user->isTeacher()) {
                $q->orWhere('target_audience', self::AUDIENCE_TEACHERS);
            }

            if ($user->isStaff()) {
                $q->orWhere('target_audience', self::AUDIENCE_STAFF);
            }
        });
    }

    /**
     * Check if announcement is published
     */
    public function isPublished(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->published_at > now()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        return true;
    }

    /**
     * Get excerpt of content
     */
    public function getExcerptAttribute(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->content), 150);
    }
}

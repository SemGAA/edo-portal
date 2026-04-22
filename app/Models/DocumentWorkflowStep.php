<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentWorkflowStep extends Model
{
    use HasFactory;

    public const ROLE_DEPARTMENT_HEAD = 'department_head';
    public const ROLE_APPROVER = 'approver';
    public const ROLE_OFFICE = 'office';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SKIPPED = 'skipped';

    public const ROLE_LABELS = [
        self::ROLE_DEPARTMENT_HEAD => 'Руководитель отдела',
        self::ROLE_APPROVER => 'Дополнительный согласующий',
        self::ROLE_OFFICE => 'Канцелярия',
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Ожидает старта',
        self::STATUS_IN_PROGRESS => 'В работе',
        self::STATUS_APPROVED => 'Согласовано',
        self::STATUS_REJECTED => 'Отклонено',
        self::STATUS_SKIPPED => 'Пропущено',
    ];

    protected $fillable = [
        'document_id',
        'round',
        'sequence',
        'title',
        'role_code',
        'user_id',
        'department_id',
        'status',
        'comment',
        'started_at',
        'acted_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'acted_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'department_id');
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_LABELS[$this->role_code] ?? $this->role_code;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getAssigneeNameAttribute(): string
    {
        return $this->user?->name ?? $this->role_label;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'workflow_step_id',
        'action',
        'title',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(DocumentWorkflowStep::class, 'workflow_step_id');
    }

    public function getActorNameAttribute(): string
    {
        return $this->user?->name ?? 'Система';
    }
}

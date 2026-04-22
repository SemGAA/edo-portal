<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentActionLog;
use App\Models\DocumentWorkflowStep;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 0;
    public const ROLE_EMPLOYEE = 1;
    public const ROLE_OFFICE = 2;
    public const ROLE_DEPARTMENT_HEAD = 3;

    public const ROLE_LABELS = [
        self::ROLE_ADMIN => 'Администратор',
        self::ROLE_EMPLOYEE => 'Сотрудник',
        self::ROLE_OFFICE => 'Канцелярия',
        self::ROLE_DEPARTMENT_HEAD => 'Руководитель отдела',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'position',
        'phone',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function authoredDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'author_id');
    }

    public function documentsToApprove(): HasMany
    {
        return $this->hasMany(Document::class, 'approver_id');
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(DocumentWorkflowStep::class);
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(DocumentActionLog::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function isAdmin(): bool
    {
        return (int) $this->role === self::ROLE_ADMIN;
    }

    public function isOffice(): bool
    {
        return (int) $this->role === self::ROLE_OFFICE;
    }

    public function isDepartmentHead(): bool
    {
        return (int) $this->role === self::ROLE_DEPARTMENT_HEAD;
    }

    public function canSeeAllDocuments(): bool
    {
        return $this->isAdmin() || $this->isOffice();
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_LABELS[(int) $this->role] ?? 'Сотрудник';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $table = 'quiz_attempts';

    protected $primaryKey = 'id_attempt';

    protected $fillable = [
        'id_user',
        'id_module',
        'attempt_number',
        'started_at',
        'submitted_at',
        'expires_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'id_module', 'id_module');
    }

    public function quizProgresses(): HasMany
    {
        return $this->hasMany(QuizProgress::class, 'id_attempt', 'id_attempt');
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->isAfter($this->expires_at);
    }

    public function isActive(): bool
    {
        return ! $this->isSubmitted() && ! $this->isExpired();
    }
}

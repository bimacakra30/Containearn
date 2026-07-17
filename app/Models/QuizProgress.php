<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizProgress extends Model
{
    protected $table = 'quiz_progress';

    protected $primaryKey = 'id_quiz_progress';

    protected $fillable = [
        'id_attempt',
        'id_user',
        'id_quiz',
        'selected_option',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'id_attempt', 'id_attempt');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'id_quiz', 'id_quiz');
    }
}

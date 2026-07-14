<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    protected $table = 'quiz_questions';

    protected $primaryKey = 'id_quiz';

    protected $fillable = [
        'id_module',
        'question',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'id_module', 'id_module');
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(QuizProgress::class, 'id_quiz', 'id_quiz');
    }
}

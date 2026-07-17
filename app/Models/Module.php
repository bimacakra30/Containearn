<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $table = 'module';

    protected $primaryKey = 'id_module';

    protected $fillable = [
        'id_course',
        'module_title',
        'description',
        'module_pdf_path',
        'file_exe',
        'time_limit',
        'quiz_time_limit',
        'quiz_max_attempts',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'id_course', 'id_course');
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'id_module', 'id_module')->orderBy('id_quiz');
    }

    public function labQuestions(): HasMany
    {
        return $this->hasMany(LabQuestion::class, 'id_module', 'id_module')->orderBy('id_lab');
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(ModuleProgress::class, 'id_module', 'id_module');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'id_module', 'id_module');
    }

    public function hasQuizTimer(): bool
    {
        return $this->quiz_time_limit !== null && $this->quiz_time_limit > 0;
    }
}

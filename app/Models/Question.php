<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $table = 'questions';

    protected $primaryKey = 'id_question';

    protected $fillable = [
        'id_module',
        'question',
        'output',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'id_module', 'id_module');
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(QuestionProgress::class, 'question_id', 'id_question');
    }
}

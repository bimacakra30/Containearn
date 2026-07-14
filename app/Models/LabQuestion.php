<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabQuestion extends Model
{
    protected $table = 'lab_questions';

    protected $primaryKey = 'id_lab';

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
        return $this->hasMany(QuestionProgress::class, 'id_lab', 'id_lab');
    }
}

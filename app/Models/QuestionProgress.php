<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionProgress extends Model
{
    protected $table = 'lab_progress';

    protected $primaryKey = 'id_lab_progress';

    protected $fillable = [
        'id_user',
        'id_lab',
        'submitted_code',
        'stdout',
        'stderr',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function labQuestion(): BelongsTo
    {
        return $this->belongsTo(LabQuestion::class, 'id_lab', 'id_lab');
    }
}

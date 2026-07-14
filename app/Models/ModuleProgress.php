<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleProgress extends Model
{
    protected $table = 'module_progress';

    protected $primaryKey = 'id_progress';

    protected $fillable = [
        'id_user',
        'id_module',
        'status',
        'current_question_index',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'id_module', 'id_module');
    }
}

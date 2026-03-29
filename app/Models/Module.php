<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $table = 'modules';

    protected $primaryKey = 'id_module';

    protected $fillable = [
        'id_course',
        'title',
        'description',
        'time_limit',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'id_course', 'id_course');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'id_module', 'id_module');
    }
}

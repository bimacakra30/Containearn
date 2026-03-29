<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $table = 'courses';

    protected $primaryKey = 'id_course';

    protected $fillable = [
        'course_title',
        'docker_image',
    ];

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class, 'id_course', 'id_course');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'identity_id',
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin()
    {
        return in_array($this->role, ['superadmin', 'dosen']);
    }

    public function isMahasiswa()
    {
        return $this->role === 'mahasiswa';
    }

    public function moduleProgresses(): HasMany
    {
        return $this->hasMany(ModuleProgress::class);
    }

    public function questionProgresses(): HasMany
    {
        return $this->hasMany(QuestionProgress::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $table = 'user';

    protected $primaryKey = 'id_user';

    protected $fillable = [
        'identity_id',
        'name',
        'email',
        'password',
        'role',
        'class',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];
    
    public function hasVerifiedEmail(): bool
    {
        if ($this->role !== 'mahasiswa') {
            return true;
        }

        return $this->email_verified_at !== null;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'dosen']);
    }

    public function isMahasiswa(): bool
    {
        return $this->role === 'mahasiswa';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function moduleProgresses(): HasMany
    {
        return $this->hasMany(ModuleProgress::class, 'id_user', 'id_user');
    }

    public function questionProgresses(): HasMany
    {
        return $this->hasMany(QuestionProgress::class, 'id_user', 'id_user');
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasRoles;

    protected $table = 'sys_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'birthplace',
        'birthdate',
        'gender',
        'avatar',
        'phone',
        'address',
        'is_active',
    ];

    protected $appends = ['avatar_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar 
            ? route('stream.file', $this->avatar) 
            : null;
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->avatar) {
                    return route('stream.file', $this->avatar);
                }

                if ($this->avatar == null && $this->gender == "l") {
                    return asset('avatar-fallback/blank-avatar-man.jpg');
                }

                if ($this->avatar == null && $this->gender == "p") {
                    return asset('avatar-fallback/blank-avatar-woman.jpg');
                }

                return null;
            }
        );
    }

    protected function mainRole(): Attribute
    {
        return Attribute::make(
            get: fn() => Str::ucfirst($this->roles()->first()?->name)
        );
    }
}

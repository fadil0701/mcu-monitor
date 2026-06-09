<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
	/** @use HasFactory<UserFactory> */
	use HasFactory, Notifiable, HasRoles;

	protected static function newFactory(): UserFactory
	{
		return UserFactory::new();
	}

	protected $fillable = [
		'name',
		'email',
		'password',
		'role',
		'nik_ktp',
		'nrk_pegawai',
		'is_active',
	];

	protected $hidden = [
		'password',
		'remember_token',
	];

	protected $casts = [
		'email_verified_at' => 'datetime',
		'password' => 'hashed',
	];

	public function isAdmin(): bool
	{
		return in_array($this->role, ['admin','super_admin'], true);
	}

	public function getRoleLabelAttribute(): string
	{
		return match ($this->role) {
			'super_admin' => 'Super Admin',
			'admin' => 'Admin',
			default => 'Peserta',
		};
	}
}

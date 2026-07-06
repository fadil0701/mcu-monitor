<?php

namespace App\Models;

use App\Support\UserRole;
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
		return in_array($this->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN], true);
	}

	public function isSuperAdmin(): bool
	{
		return UserRole::isSuperAdmin($this);
	}

	public function isPimpinan(): bool
	{
		return UserRole::isPimpinan($this);
	}

	public function hasStaffAccess(): bool
	{
		return UserRole::hasStaffAccess($this);
	}

	public function hasLinkedParticipant(): bool
	{
		return UserRole::hasLinkedParticipant($this);
	}

	public function canManageReschedule(): bool
	{
		return UserRole::canManageReschedule($this);
	}

	public function getRoleLabelAttribute(): string
	{
		return UserRole::label($this->role);
	}
}

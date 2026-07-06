<?php

namespace App\Support;

use App\Models\Participant;
use App\Models\User;

final class UserRole
{
    public const SUPER_ADMIN = 'super_admin';

    public const ADMIN = 'admin';

    public const PIMPINAN = 'pimpinan';

    public const PESERTA = 'user';

    /** @return list<string> */
    public static function staffRoles(): array
    {
        return [self::SUPER_ADMIN, self::ADMIN, self::PIMPINAN];
    }

    public static function hasStaffAccess(User $user): bool
    {
        return in_array($user->role, self::staffRoles(), true);
    }

    public static function isSuperAdmin(User $user): bool
    {
        return $user->role === self::SUPER_ADMIN;
    }

    public static function isAdmin(User $user): bool
    {
        return $user->role === self::ADMIN;
    }

    public static function isPimpinan(User $user): bool
    {
        return $user->role === self::PIMPINAN;
    }

    public static function isPeserta(User $user): bool
    {
        return $user->role === self::PESERTA;
    }

    public static function label(string $role): string
    {
        return match ($role) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::PIMPINAN => 'Pimpinan',
            default => 'Peserta',
        };
    }

    public static function normalize(string $role): string
    {
        return $role === 'peserta' ? self::PESERTA : $role;
    }

    public static function displayRole(string $role): string
    {
        return $role === self::PESERTA ? 'peserta' : $role;
    }

    public static function hasLinkedParticipant(User $user): bool
    {
        if ($user->nik_ktp === null || $user->nik_ktp === '') {
            return false;
        }

        return Participant::query()->where('nik_ktp', $user->nik_ktp)->exists();
    }

    public static function canManageReschedule(User $user): bool
    {
        return in_array($user->role, [self::SUPER_ADMIN, self::PIMPINAN], true);
    }

    public static function canManageParticipants(User $user): bool
    {
        return in_array($user->role, [self::SUPER_ADMIN, self::ADMIN], true);
    }

    public static function canManageUsers(User $user): bool
    {
        return in_array($user->role, [self::SUPER_ADMIN, self::ADMIN], true);
    }

    public static function canAssignRoles(User $user): bool
    {
        return self::isSuperAdmin($user);
    }

    public static function canCreateUserRole(User $actor, string $targetRole): bool
    {
        $targetRole = self::normalize($targetRole);

        if (self::isSuperAdmin($actor)) {
            return in_array($targetRole, [self::SUPER_ADMIN, self::ADMIN, self::PIMPINAN, self::PESERTA], true);
        }

        if (self::isAdmin($actor)) {
            return $targetRole === self::PESERTA;
        }

        return false;
    }

    public static function canEditUser(User $actor, User $target): bool
    {
        if (! self::canManageUsers($actor)) {
            return false;
        }

        if (self::isSuperAdmin($actor)) {
            return true;
        }

        return $target->role === self::PESERTA;
    }

    public static function canDeleteUser(User $actor, User $target): bool
    {
        if (! self::canManageUsers($actor)) {
            return false;
        }

        if ($actor->id === $target->id) {
            return false;
        }

        if (self::isSuperAdmin($actor)) {
            return true;
        }

        return $target->role === self::PESERTA;
    }

    /** @return list<string> */
    public static function notifiableStaffRoles(): array
    {
        return [self::SUPER_ADMIN, self::ADMIN, self::PIMPINAN];
    }
}

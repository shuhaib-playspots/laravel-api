<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Sanctum\NewAccessToken;

class TokenRepository
{
    public function createToken(User $user, array $deviceInfo): NewAccessToken
    {
        $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 43200));

        $newToken = $user->createToken(
            name: $deviceInfo['device_name'] ?? 'Unknown Device',
            abilities: ['*'],
            expiresAt: $expiresAt,
        );

        $newToken->accessToken->forceFill([
            'device_name' => $deviceInfo['device_name'],
            'device_type' => $deviceInfo['device_type'],
            'ip_address'  => $deviceInfo['ip_address'],
            'user_agent'  => $deviceInfo['user_agent'],
        ])->save();

        return $newToken;
    }

    public function getDevicesForUser(User $user): Collection
    {
        return $user->tokens()->orderByDesc('last_used_at')->get();
    }

    public function revokeToken(User $user, int $tokenId): bool
    {
        return (bool) $user->tokens()->where('id', $tokenId)->delete();
    }

    public function revokeAllTokens(User $user): int
    {
        return $user->tokens()->delete();
    }
}

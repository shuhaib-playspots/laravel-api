<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\TokenRepository;

class AuthService
{
    public function __construct(
        private readonly TokenRepository $tokens,
    ) {}

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function logoutAll(User $user): int
    {
        return $this->tokens->revokeAllTokens($user);
    }

    public function getDevices(User $user): array
    {
        $currentTokenId = $user->currentAccessToken()->id;

        return $this->tokens
            ->getDevicesForUser($user)
            ->map(fn ($token) => [
                'id'           => $token->id,
                'device_name'  => $token->device_name ?? $token->name,
                'device_type'  => $token->device_type,
                'ip_address'   => $token->ip_address,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at'   => $token->created_at->toIso8601String(),
                'expires_at'   => $token->expires_at?->toIso8601String(),
                'is_current'   => $token->id === $currentTokenId,
            ])
            ->all();
    }

    public function revokeDevice(User $user, int $tokenId): bool
    {
        return $this->tokens->revokeToken($user, $tokenId);
    }

    public function refresh(User $user, array $deviceInfo): array
    {
        $user->currentAccessToken()->delete();
        $token = $this->tokens->createToken($user, $deviceInfo);

        return [
            'token'  => $token->plainTextToken,
            'device' => [
                'id'          => $token->accessToken->id,
                'device_name' => $token->accessToken->device_name,
                'device_type' => $token->accessToken->device_type,
                'expires_at'  => $token->accessToken->expires_at?->toIso8601String(),
            ],
        ];
    }
}

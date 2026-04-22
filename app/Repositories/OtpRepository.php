<?php

namespace App\Repositories;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

class OtpRepository
{
    /**
     * Invalidate all previous unused OTPs for the email, then create a fresh one.
     */
    public function create(string $email, string $plainCode, int $ttlMinutes): OtpCode
    {
        // Expire any existing unused OTPs for this email
        OtpCode::where('email', $email)->whereNull('used_at')->delete();

        return OtpCode::create([
            'email'      => $email,
            'code'       => Hash::make($plainCode),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    /**
     * Find the latest unused, unexpired OTP record for the given email.
     */
    public function findLatestValid(string $email): ?OtpCode
    {
        return OtpCode::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();
    }

    public function markUsed(OtpCode $otp): void
    {
        $otp->update(['used_at' => now()]);
    }

    /**
     * Count how many OTPs were sent to this email in the last $minutes minutes.
     */
    public function recentSendCount(string $email, int $minutes): int
    {
        return OtpCode::where('email', $email)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->count();
    }
}

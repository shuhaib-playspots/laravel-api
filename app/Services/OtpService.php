<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Repositories\OtpRepository;
use App\Repositories\TokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    private const OTP_TTL_MINUTES      = 10;
    private const OTP_MAX_SENDS        = 3;   // max sends per 10-minute window
    private const OTP_SEND_WINDOW      = 10;  // minutes
    private const REG_TOKEN_TTL        = 30;  // minutes a registration token stays valid

    public function __construct(
        private readonly OtpRepository   $otps,
        private readonly UserRepository  $users,
        private readonly TokenRepository $tokens,
    ) {}

    /**
     * Generate and email an OTP for the given address.
     * Aborts with 429 if the send limit is exceeded.
     */
    public function send(string $email): void
    {
        $recentCount = $this->otps->recentSendCount($email, self::OTP_SEND_WINDOW);

        if ($recentCount >= self::OTP_MAX_SENDS) {
            abort(429, 'Too many OTP requests. Please wait a few minutes before trying again.');
        }

        $plain = $this->generateCode();

        $this->otps->create($email, $plain, self::OTP_TTL_MINUTES);

        Mail::to($email)->send(new OtpMail($plain, self::OTP_TTL_MINUTES));
    }

    /**
     * Verify the OTP and return one of two outcomes:
     *
     *  - Existing user  → [ 'status' => 'authenticated',         'user' => ..., 'token' => ..., 'device' => ... ]
     *  - New user       → [ 'status' => 'registration_required', 'registration_token' => ... ]
     */
    public function verify(string $email, string $plainCode, array $deviceInfo): array
    {
        $otpRecord = $this->otps->findLatestValid($email);

        if (! $otpRecord || ! Hash::check($plainCode, $otpRecord->code)) {
            abort(422, 'Invalid or expired OTP.');
        }

        $this->otps->markUsed($otpRecord);

        $user = $this->users->findByEmail($email);

        if ($user) {
            // Existing user — issue a full auth token
            $token = $this->tokens->createToken($user, $deviceInfo);

            return [
                'success'=> true,
                'user'   => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
                'token'  => $token->plainTextToken,
                'device' => [
                    'id'          => $token->accessToken->id,
                    'device_name' => $token->accessToken->device_name,
                    'device_type' => $token->accessToken->device_type,
                    'expires_at'  => $token->accessToken->expires_at?->toIso8601String(),
                    ],
                'is_new_user' => false,
            ];
        }

        // New user — issue a short-lived registration token
        $registrationToken = Str::random(64);

        Cache::put(
            $this->regCacheKey($registrationToken),
            $email,
            now()->addMinutes(self::REG_TOKEN_TTL),
        );

        return [
            'success'            => true,
            'registration_token' => $registrationToken,
            'is_new_user'        => true
        ];
    }

    /**
     * Complete the profile for a new user identified by the registration token.
     * Returns the same payload shape as a normal login.
     */
    public function completeProfile(string $registrationToken, string $name, string $mobile, string $gender, string $dob, array $deviceInfo): array
    {
        $cacheKey = $this->regCacheKey($registrationToken);
        $email    = Cache::get($cacheKey);

        if (! $email) {
            abort(422, 'Invalid or expired registration token.');
        }

        Cache::forget($cacheKey);

        $user  = $this->users->create($name, $email, Str::random(32), $mobile, $gender, $dob);
        $token = $this->tokens->createToken($user, $deviceInfo);

        return [
            'success'=> true,
            'user'   => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'mobile' => $user->mobile,
                'gender' => $user->gender,
                'dob'    => $user->dob?->toDateString(),
            ],
            'token'  => $token->plainTextToken,
            'device' => [
                'id'          => $token->accessToken->id,
                'device_name' => $token->accessToken->device_name,
                'device_type' => $token->accessToken->device_type,
                'expires_at'  => $token->accessToken->expires_at?->toIso8601String(),
            ],
        ];
    }

    // -------------------------------------------------------------------------

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function regCacheKey(string $token): string
    {
        return 'reg_token:' . $token;
    }
}

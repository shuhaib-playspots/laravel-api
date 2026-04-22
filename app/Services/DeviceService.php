<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceService
{
    public function extract(Request $request, ?string $providedName = null): array
    {
        $userAgent  = $request->userAgent() ?? '';
        $deviceType = $this->detectType($userAgent);
        $deviceName = $providedName ?? $this->detectName($userAgent, $deviceType);

        return [
            'device_name' => $deviceName,
            'device_type' => $deviceType,
            'ip_address'  => $request->ip(),
            'user_agent'  => substr($userAgent, 0, 500),
        ];
    }

    private function detectType(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        if (empty($ua) || str_contains($ua, 'curl') || str_contains($ua, 'postman') || str_contains($ua, 'insomnia')) {
            return 'api';
        }

        return 'desktop';
    }

    private function detectName(string $userAgent, string $deviceType): string
    {
        $ua = strtolower($userAgent);

        if (str_contains($ua, 'chrome'))   return ucfirst($deviceType) . ' / Chrome';
        if (str_contains($ua, 'firefox'))  return ucfirst($deviceType) . ' / Firefox';
        if (str_contains($ua, 'safari'))   return ucfirst($deviceType) . ' / Safari';
        if (str_contains($ua, 'edge'))     return ucfirst($deviceType) . ' / Edge';
        if (str_contains($ua, 'postman'))  return 'Postman';
        if (str_contains($ua, 'insomnia')) return 'Insomnia';

        return ucfirst($deviceType) . ' Device';
    }
}

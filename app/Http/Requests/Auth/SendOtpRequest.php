<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SendOtpRequest
 *
 * Validates the incoming request for sending a one-time password (OTP).
 * Ensures the provided email address is present, properly formatted,
 * and resolvable via DNS before the OTP is dispatched.
 *
 * @package App\Http\Requests\Auth
 * @author  shuhaib malik
 * @date    2026-04-22
 */
class SendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorised to make this request.
     *
     * Always returns true — authorisation is handled at the route/middleware level.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Rules:
     * - `email` — Required; must be a valid string, pass RFC 5321 format validation,
     *             have a resolvable DNS MX/A record, and be no longer than 255 characters.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255'],
        ];
    }
}

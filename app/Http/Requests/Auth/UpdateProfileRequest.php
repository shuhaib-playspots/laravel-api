<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateProfileRequest
 *
 * Validates and pre-processes the incoming request for updating an authenticated
 * user's profile. All fields are optional — only submitted fields are validated
 * and updated, enabling partial profile edits. The mobile number is sanitised to
 * E.164 format before validation rules are applied.
 *
 * @package App\Http\Requests\Auth
 * @author  shuhaib malik
 * @date    2026-04-27
 */
class UpdateProfileRequest extends FormRequest
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
     * Normalise the mobile number before validation rules are applied.
     *
     * Performs the following transformations on the raw `mobile` input when present:
     * - Strips all characters except digits and the leading `+`.
     * - Converts `91XXXXXXXXXX` (Indian number without `+`) → `+91XXXXXXXXXX`.
     * - Converts `9715XXXXXXXX` (UAE number without `+`) → `+9715XXXXXXXX`.
     *
     * The sanitised value is merged back into the request payload so that
     * subsequent validation rules operate on the cleaned string.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->mobile) {
            $mobile = trim($this->mobile);

            // Remove unwanted characters
            $mobile = preg_replace('/[^\d+]/', '', $mobile);

            // Convert 91XXXXXXXXXX → +91XXXXXXXXXX
            if (preg_match('/^91[6-9]\d{9}$/', $mobile)) {
                $mobile = '+' . $mobile;
            }

            // Convert 971XXXXXXXXX → +971XXXXXXXXX
            if (preg_match('/^9715\d{8}$/', $mobile)) {
                $mobile = '+' . $mobile;
            }

            $this->merge(['mobile' => $mobile]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * All fields use `sometimes` so they are only validated when present in the
     * request payload, allowing callers to submit a partial update.
     *
     * Rules:
     * - `name`   — Optional; Unicode letters, spaces, and hyphens only; max 255 chars.
     * - `mobile` — Optional; must match one of:
     *                • `+91XXXXXXXXXX` (Indian E.164)
     *                • `0XXXXXXXXXX`   (Indian with leading zero)
     *                • `XXXXXXXXXX`    (Indian 10-digit without prefix)
     *                • `+9715XXXXXXXX` (UAE E.164)
     * - `gender` — Optional; one of `male`, `female`, or `other`.
     * - `dob`    — Optional date; must be in the past and after 1900-01-01.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'string', 'max:255', 'regex:/^[\pL\s\-]+$/u'],
            'mobile' => [
                'sometimes',
                'string',
                'regex:/^(?:\+91[6-9]\d{9}|0[6-9]\d{9}|[6-9]\d{9}|\+9715\d{8})$/'
            ],
            'gender' => ['sometimes', 'in:male,female,other'],
            'dob'    => ['sometimes', 'date', 'before:today', 'after:1900-01-01'],
        ];
    }
}

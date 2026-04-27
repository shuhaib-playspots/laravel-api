<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\AuthService;
use App\Services\DeviceService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

/**
 * Class AuthController
 *
 * Handles API authentication flows including OTP-based registration/login,
 * profile completion, session management, and device revocation.
 *
 * @package App\Http\Controllers\Api
 * @author  shuhaib malik
 * @date    2026-04-22
 */
class AuthController extends Controller
{
    /**
     * AuthController constructor.
     *
     * @param AuthService   $auth   Service responsible for authentication operations.
     * @param OtpService    $otp    Service responsible for OTP generation and verification.
     * @param DeviceService $device Service responsible for extracting device information.
     */
    public function __construct(
        private readonly AuthService   $auth,
        private readonly OtpService   $otp,
        private readonly DeviceService $device,
    ) {}

    // -------------------------------------------------------------------------
    // OTP flow
    // -------------------------------------------------------------------------

    /**
     * Send an OTP to the given email address.
     *
     * Dispatches a one-time password to the validated email so the user can
     * proceed with the verification step.
     *
     * @param  SendOtpRequest $request Validated request containing the user's email.
     * @return JsonResponse            JSON response with success status and message.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OTP sent to your email address."
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            $this->otp->send($request->validated('email'));

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email address.'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify the OTP submitted by the user.
     *
     * Validates the OTP against the stored value for the given email. On success,
     * returns a registration token (for new users) or an access token (for
     * existing users), along with device session details.
     *
     * @param  VerifyOtpRequest $request Validated request containing email, OTP, and device name.
     * @return JsonResponse              JSON response with verification result payload.
     *
     * @response 200 mixed Verification result from OtpService::verify()
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->otp->verify(
                $request->validated('email'),
                $request->validated('otp'),
                $this->device->extract($request, $request->validated('device_name')),
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete the user's profile after OTP verification.
     *
     * Uses the registration token obtained from OTP verification to finalise the
     * user's account by saving personal details and registering the device session.
     *
     * @param  CompleteProfileRequest $request Validated request containing the registration
     *                                         token, name, mobile, gender, dob, and device name.
     * @return JsonResponse                    JSON response with the newly created user and
     *                                         access token, returned with HTTP 201.
     *
     * @response 201 mixed Profile completion result from OtpService::completeProfile()
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        try {
            $result = $this->otp->completeProfile(
                $request->validated('registration_token'),
                $request->validated('name'),
                $request->validated('mobile'),
                $request->validated('gender'),
                $request->validated('dob'),
                $this->device->extract($request, $request->validated('device_name')),
            );

            return response()->json($result, 201);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Shared protected routes
    // -------------------------------------------------------------------------

    /**
     * Log out the currently authenticated user from the current device.
     *
     * Revokes the access token associated with the current request, effectively
     * ending the session on this device only.
     *
     * @param  Request      $request The incoming HTTP request (must be authenticated).
     * @return JsonResponse          JSON response confirming successful logout.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Logged out successfully."
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->auth->logout($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log out the currently authenticated user from all devices.
     *
     * Revokes every access token belonging to the authenticated user and returns
     * the total number of revoked device sessions.
     *
     * @param  Request      $request The incoming HTTP request (must be authenticated).
     * @return JsonResponse          JSON response with a message and the count of revoked devices.
     *
     * @response 200 {
     *   "message": "Logged out from all devices.",
     *   "devices_revoked": 3
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $count = $this->auth->logoutAll($request->user());

            return response()->json([
                'message'         => 'Logged out from all devices.',
                'devices_revoked' => $count
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retrieve all active device sessions for the authenticated user.
     *
     * Returns a list of all devices (tokens) currently associated with the user's
     * account, useful for session management in client applications.
     *
     * @param  Request      $request The incoming HTTP request (must be authenticated).
     * @return JsonResponse          JSON response containing an array of device sessions.
     *
     * @response 200 {
     *   "devices": [...]
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function devices(Request $request): JsonResponse
    {
        try {
            return response()->json(['devices' => $this->auth->getDevices($request->user())]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke a specific device session by token ID.
     *
     * Allows the authenticated user to remotely log out a particular device by
     * providing its token ID. Returns 404 if the session is not found.
     *
     * @param  Request      $request The incoming HTTP request (must be authenticated).
     * @param  int          $tokenId The ID of the Sanctum personal access token to revoke.
     * @return JsonResponse          JSON response confirming revocation, or 404 if not found.
     *
     * @response 200 {
     *   "message": "Device session revoked."
     * }
     * @response 404 {
     *   "message": "Device session not found."
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function revokeDevice(Request $request, int $tokenId): JsonResponse
    {
        try {
            if (! $this->auth->revokeDevice($request->user(), $tokenId)) {
                return response()->json(['message' => 'Device session not found.'], 404);
            }

            return response()->json(['message' => 'Device session revoked.']);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return the authenticated user's profile information.
     *
     * Retrieves and returns the core profile fields of the currently authenticated
     * user, including ID, name, email, mobile number, gender, and date of birth.
     *
     * @param  Request      $request The incoming HTTP request (must be authenticated).
     * @return JsonResponse          JSON response containing the user's profile data.
     *
     * @response 200 {
     *   "success": true,
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "mobile": "+1234567890",
     *     "gender": "male",
     *     "dob": "1990-01-01"
     *   }
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'user'    => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'mobile' => $user->mobile,
                    'gender' => $user->gender,
                    'dob'    => $user->dob?->toDateString(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the authenticated user's profile details.
     *
     * Accepts a partial payload — only the fields included in the request are
     * updated. At least one of `name`, `mobile`, `gender`, or `dob` must be
     * provided. Returns the full, up-to-date profile after the update.
     *
     * @param  UpdateProfileRequest $request Validated request containing any subset of
     *                                       name, mobile, gender, and dob.
     * @return JsonResponse                  JSON response with success status and the
     *                                       updated user profile.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Profile updated successfully.",
     *   "user": {
     *     "id": 1,
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "mobile": "+911234567890",
     *     "gender": "female",
     *     "dob": "1995-06-15"
     *   }
     * }
     * @response 422 {
     *   "message": "The name field must only contain letters, spaces, and hyphens.",
     *   "errors": { "name": ["..."] }
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No updatable fields provided.',
                ], 422);
            }

            $user = $this->auth->updateProfile($request->user(), $data);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user'    => $user,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh the access token for the authenticated user.
     *
     * Rotates the current access token by revoking it and issuing a new one for
     * the same device. The device name is derived from the existing token's metadata.
     *
     * @param  Request      $request The incoming HTTP request (must be authenticated).
     * @return JsonResponse          JSON response with success status, message, and the new token payload.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Token refreshed.",
     *   "token": "new-access-token",
     *   ...
     * }
     * @response 500 {
     *   "success": false,
     *   "message": "Error description"
     * }
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user       = $request->user();
            $deviceInfo = $this->device->extract($request, $user->currentAccessToken()->device_name);
            $payload    = $this->auth->refresh($user, $deviceInfo);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed.',
                ...$payload
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

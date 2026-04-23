<?php
    
 namespace App\Http\Controllers\PoliceOfficer;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PoliceOfficer\UpdateProfileRequest;
use App\Http\Requests\PoliceOfficer\UpdateFcmTokenRequest;
use App\Http\Services\AuthService;
use App\Http\Resources\ProfileResource;
use App\Enums\RoleUserEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $attrs = $request->validated();
        $user = $this->authService->loginApi($attrs, RoleUserEnum::Police_officer);
        $profile = (new ProfileResource($user))->resolve();
        $token = (string) ($user->access_token ?? '');

        return response()->json([
            'status_code' => 200,
            'message' => 'Success',
            'token' => $token,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => [
                ...$profile,
                'token' => $token,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function profile(): JsonResponse
    {
        $profile = $this->authService->profile();

        return $this->success(new ProfileResource($profile));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'status_code' => 200,
            'message' => 'Profile updated successfully',
            'data' => new ProfileResource($user),
        ]);
    }
    
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->token();

        if ($token) {
            $token->revoke();
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Logged out successfully',
            'data' => [],
        ]);
    }

    public function updateFcmToken(UpdateFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'fcm_token' => $request->validated()['fcm_token'],
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'status_code' => 200,
            'message' => 'FCM token updated successfully',
            'data' => [
                'fcm_token_saved' => true,
            ],
        ]);
    }
}


   

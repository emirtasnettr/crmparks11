<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $throttleKey = 'api-login:'.Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return ApiResponse::error(
                "Çok fazla başarısız giriş denemesi. Lütfen {$seconds} saniye sonra tekrar deneyin.",
                429,
            );
        }

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return ApiResponse::error('E-posta veya şifre hatalı.', 401);
        }

        if ($user->status === Status::Inactive || $user->status === Status::Suspended) {
            return ApiResponse::error('Hesabınız pasif veya askıya alınmış durumda.', 403);
        }

        RateLimiter::clear($throttleKey);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $this->activityLog->log('login', $user, description: 'API girişi yapıldı');

        $token = $user->createToken($credentials['device_name'] ?? 'api')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ], 'Giriş başarılı');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success($this->userPayload($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->activityLog->log('logout', $user, description: 'API çıkışı yapıldı');

        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Çıkış başarılı');
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        $user->loadMissing('roles');

        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status?->value ?? (string) $user->status,
            'user_type' => $user->user_type?->value ?? (string) $user->user_type,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}

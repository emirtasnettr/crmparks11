<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
        ], [], [
            'email' => 'e-posta',
        ]);

        $status = Password::sendResetLink($credentials);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return back()->with('success', 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [], [
            'email' => 'e-posta',
            'password' => 'şifre',
            'password_confirmation' => 'şifre tekrarı',
        ]);

        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                $this->activityLog->log(
                    'password_changed',
                    $user,
                    description: "{$user->name} şifresini sıfırladı.",
                );
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return redirect()
            ->route('login')
            ->with('success', 'Şifreniz güncellendi. Yeni şifrenizle giriş yapabilirsiniz.');
    }
}

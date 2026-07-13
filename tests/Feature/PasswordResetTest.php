<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\LookupTableSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            LookupTableSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
    }

    public function test_forgot_password_page_is_available(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Şifremi unuttum');
    }

    public function test_guest_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_admin_can_send_password_reset_for_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $user = User::factory()->create(['email' => 'target@example.com']);

        $response = $this->actingAs($admin)->post(route('users.reset-password', $user->id));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_admin_reset_requires_update_permission(): void
    {
        Notification::fake();

        $actor = User::factory()->create();
        $actor->assignRole('operations_specialist');

        $user = User::factory()->create();

        $this->actingAs($actor)
            ->post(route('users.reset-password', $user->id))
            ->assertForbidden();

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'resetme@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_login_page_shows_forgot_password_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Şifremi unuttum')
            ->assertSee(route('password.request'), false);
    }
}

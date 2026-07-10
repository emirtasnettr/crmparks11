<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject(config('crmlog.name', 'CRMLog').' — Şifre Sıfırlama')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line('Hesabınız için şifre sıfırlama talebi aldık.')
            ->action('Şifreyi Sıfırla', $url)
            ->line("Bu bağlantı {$expire} dakika geçerlidir.")
            ->line('Bu talebi siz oluşturmadıysanız bu e-postayı yok sayabilirsiniz.')
            ->salutation(config('crmlog.name', 'CRMLog'));
    }
}

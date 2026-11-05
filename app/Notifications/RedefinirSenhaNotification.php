<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * E-mail de recuperação de senha em português.
 *
 * É enfileirado para que o tempo de resposta da solicitação seja o mesmo
 * para e-mails cadastrados e não cadastrados (mitiga enumeração por timing).
 */
class RedefinirSenhaNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $minutosExpiracao = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Recuperação de senha — OpenAir Metrics')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta no OpenAir Metrics.')
            ->action('Redefinir senha', $this->resetUrl($notifiable))
            ->line("Este link expira em {$minutosExpiracao} minutos.")
            ->line('Se você não solicitou a redefinição, ignore este e-mail. Sua senha atual continuará válida.')
            ->salutation('Equipe OpenAir Metrics');
    }
}

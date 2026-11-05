<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail de convite e ativação de conta para novos usuários.
 *
 * Enviado quando um administrador cadastra um novo usuário no sistema.
 * Contém um token seguro para que o próprio usuário defina sua senha e endereço.
 */
class ConviteCadastroNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $minutosExpiracao = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 30);
        $url = route('cadastro.completar', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Ativação de conta — OpenAir Metrics')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Você foi cadastrado na plataforma OpenAir Metrics como {$notifiable->nivel_label}.")
            ->line('Por diretrizes de segurança, o criador da conta não possui acesso à sua senha. Para ativar o seu acesso, definir sua senha e informar seu endereço (opcional), clique no botão abaixo:')
            ->action('Completar cadastro e ativar conta', $url)
            ->line("Este link de ativação expira em {$minutosExpiracao} minutos.")
            ->line('A nova senha deverá conter no mínimo 15 caracteres (conforme diretrizes NIST SP 800-63B Rev. 4).')
            ->line('Se você não reconhece este cadastro, nenhuma ação é necessária.')
            ->salutation('Equipe OpenAir Metrics');
    }
}

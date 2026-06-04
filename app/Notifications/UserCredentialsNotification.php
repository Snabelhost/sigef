<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Mail\Mailables\Attachment;

class UserCredentialsNotification extends Notification
{
    use Queueable;

    protected string $plainPassword;
    protected bool $isNewUser;

    public function __construct(string $plainPassword, bool $isNewUser = true)
    {
        $this->plainPassword = $plainPassword;
        $this->isNewUser = $isNewUser;
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if (!empty($notifiable->email) && \App\Models\SystemSetting::isMailConfigured()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = url('/login');

        // Embed logo as base64
        $logoPath = public_path('images/logo-sigef.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $introText = $this->isNewUser
            ? 'Foi criada uma conta no sistema SIGEF para si. Utilize as credenciais abaixo para aceder ao sistema.'
            : 'A sua palavra-passe foi actualizada no sistema SIGEF. Utilize as novas credenciais abaixo.';

        $subject = $this->isNewUser
            ? 'Bem-vindo ao SIGEF — As suas credenciais'
            : 'SIGEF — Palavra-passe actualizada';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.user-credentials', [
                'userName' => $notifiable->name,
                'userEmail' => $notifiable->email,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => $loginUrl,
                'logoBase64' => $logoBase64,
                'introText' => $introText,
                'isNewUser' => $this->isNewUser,
            ]);
    }
}

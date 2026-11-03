<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberTemporaryPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $temporaryPassword,
        public bool $isNewRegistration = true
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        /** @var Member $notifiable */
        $subject = $this->isNewRegistration
            ? 'Selamat Datang di GymHub - Akun & Password Sementara Anda'
            : 'Reset Password Sementara Akun GymHub';

        $actionText = $this->isNewRegistration ? 'Pendaftaran akun member berhasil' : 'Permintaan reset password berhasil';

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Halo, {$notifiable->name}!")
            ->line("{$actionText}. Berikut adalah rincian akun member gym Anda:")
            ->line("**Kode Member:** {$notifiable->member_code}")
            ->line("**Nomor Handphone:** {$notifiable->phone}")
            ->line("**Email:** {$notifiable->email}")
            ->line("**Password Sementara:** {$this->temporaryPassword}")
            ->line('Silakan gunakan nomor HP / email dan password sementara di atas untuk login ke Aplikasi Mobile Member.')
            ->line('Demi keamanan akun Anda, harap segera lakukan penggantian password atau PIN setelah berhasil login melalui menu pengaturan akun.')
            ->salutation('Salam bugar, Tim GymHub');
    }
}

<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmAccountDeletion extends Notification
{
    public function __construct(public string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Konfirmasi penghapusan akun ExamRoom')
            ->greeting('Permintaan hapus akun ExamRoom')
            ->line('Kami menerima permintaan penghapusan akun untuk alamat email ini. Belum ada data yang dihapus.')
            ->line('Jika dilanjutkan, profil guru, sesi ujian, data peserta terkait dan token akses ExamRoom akan dihapus. Tindakan ini tidak dapat dibatalkan dan sesi ujian akan berhenti dapat diakses.')
            ->action('Tinjau dan konfirmasi penghapusan', $this->url)
            ->line('Tautan berlaku selama 1 jam. Membuka tautan saja tidak menghapus akun; Anda harus mengonfirmasi pada halaman berikutnya.')
            ->line('Jawaban di Google Forms tidak dihapus oleh ExamRoom. Hubungi pemilik formulir untuk pengelolaan data tersebut.')
            ->line('Jika Anda tidak meminta penghapusan, abaikan email ini. Untuk bantuan, hubungi '.(config('examroom.support_email') ?: 'support@examroom.smkn4tpi.sch.id').'.');
    }
}

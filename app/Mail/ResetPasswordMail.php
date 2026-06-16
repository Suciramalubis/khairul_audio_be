<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pemulihan Password - Khairul Audio',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '
            <div style="font-family: Arial, sans-serif; padding: 20px; color: #333;">
                <h2 style="color: #ef4444;">Permintaan Pemulihan Akun</h2>
                <p>Halo,</p>
                <p>Kami menerima permintaan untuk mereset password akun Anda di Khairul Audio. Berikut adalah kode verifikasi (OTP) Anda:</p>
                <h1 style="letter-spacing: 5px; color: #111827; background: #f3f4f6; padding: 10px 20px; display: inline-block; border-radius: 5px;">' . $this->otp . '</h1>
                <p>Kode ini berlaku selama <strong>10 menit</strong>.</p>
                <p style="font-size: 12px; color: #666; mt-4">Jika Anda tidak merasa meminta reset password, abaikan email ini. Akun Anda tetap aman.</p>
            </div>'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
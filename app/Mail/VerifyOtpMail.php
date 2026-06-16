<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    /**
     * Create a new message instance.
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kode Verifikasi Akun - Khairul Audio',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            // Langsung menggunakan HTML String agar tidak perlu buat file view terpisah
            htmlString: '
            <div style="font-family: Arial, sans-serif; padding: 20px; color: #333;">
                <h2 style="color: #f59e0b;">Selamat Datang di Khairul Audio!</h2>
                <p>Halo, terima kasih telah mendaftar.</p>
                <p>Berikut adalah kode verifikasi OTP Anda:</p>
                <h1 style="letter-spacing: 5px; color: #111827; background: #f3f4f6; padding: 10px 20px; display: inline-block; border-radius: 5px;">' . $this->otp . '</h1>
                <p>Kode ini berlaku selama <strong>10 menit</strong>. Jangan berikan kode ini kepada siapa pun.</p>
            </div>'
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
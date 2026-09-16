<?php

// Panggil namespace PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Panggil Autoloader dari Composer (PENTING!)
require_once __DIR__ . '/../../vendor/autoload.php';

if (file_exists(__DIR__ . '/../../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
}

class Mailer {

    private static function env(string $key, ?string $default = null): ?string {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    public static function sendEmail($to, $subject, $message) {
        // Buat instance PHPMailer baru
        $mail = new PHPMailer(true);

        try {
            // ===============================================
            // 1. SETTING SERVER GMAIL (SMTP)
            // ===============================================
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Nyalakan ini kalau mau lihat log error detail
            $mail->isSMTP();
            $mail->Host       = self::env('MAIL_HOST', '');
            $mail->SMTPAuth   = true;
            
            $mail->Username   = self::env('MAIL_USERNAME', '');
            $mail->Password   = self::env('MAIL_PASSWORD', '');
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // ===============================================
            // 2. PENGIRIM & PENERIMA
            // ===============================================
            $mail->setFrom(self::env('MAIL_FROM', ''), self::env('MAIL_FROM_NAME', 'GMS Library Admin'));
            $mail->addAddress($to); // Email tujuan (User)

            // ===============================================
            // 3. ISI KONTEN
            // ===============================================
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message); // Versi teks polos

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Jika gagal, bisa cek errornya disini: $mail->ErrorInfo
            return false;
        }
    }
}

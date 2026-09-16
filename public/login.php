<?php
// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;
// 1. Cek status session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Siapkan variabel untuk menampung pesan
$msgType = '';
$msgContent = '';

// 3. Cek apakah ada error dari AuthController
if (isset($_SESSION['error_message'])) {
    $msgType = 'error';
    $msgContent = $_SESSION['error_message'];
    // Hapus session agar pesan tidak muncul terus saat direfresh
    unset($_SESSION['error_message']); 
} 
// 4. Cek apakah ada pesan sukses (misal: habis register)
elseif (isset($_SESSION['alert_success'])) {
    $msgType = 'success';
    $msgContent = $_SESSION['alert_success'];
    unset($_SESSION['alert_success']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GMS Library</title>
    
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* --- CSS KHUSUS UNTUK ALERT INLINE --- */
        
        .alert-box {
            padding: 12px 15px;
            margin-top: 10px;    /* Jarak dari input password */
            margin-bottom: 15px; /* Jarak ke tombol login */
            border-radius: 6px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.4;
        }

        /* Gaya untuk Error (Merah) */
        .alert-box.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }

        /* Gaya untuk Sukses (Hijau) */
        .alert-box.success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #4ade80;
        }

        /* Animasi muncul halus */
        .fade-in-message {
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>

    <div class="login-background fade-in">
        <div class="login-container">

            <div class="left-panel slide-left">
                <h1><span class="bold">GMS</span> Library</h1>
                <p>
                    GMS Library adalah perpustakaan modern dengan koleksi buku dan sumber digital yang beragam.
                    Menyediakan ruang baca nyaman, area diskusi, serta layanan peminjaman untuk menunjang belajar
                    dan penelitian pengunjung.
                </p>
            </div>

            <div class="right-panel slide-right">
                <h2>Login</h2>

                <form id="loginForm" action="index.php?controller=auth&action=login" method="POST">
                    
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan Username" required autocomplete="username">

                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan Password" required autocomplete="current-password">

                    <?php if (!empty($msgContent)): ?>
                        <div class="alert-box <?= $msgType ?> fade-in-message">
                            <span><?= $msgType === 'error' ? '⚠️' : '✅'; ?></span>
                            <span><?= htmlspecialchars($msgContent, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn-login" style="margin-top: 10px;">Masuk</button>
                </form>

                <div style="margin-top: 20px; text-align: center; font-size: 0.9rem;">
                    Belum punya akun? <a href="index.php?controller=auth&action=register" style="font-weight:bold; color: #7b0000;">Daftar Di Sini</a>
                </div>
            </div>
            
            <script src="js/validation.js"></script>
        </div>
    </div>

</body>
</html>

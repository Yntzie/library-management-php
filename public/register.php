<?php
// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - GMS Library</title>
<link rel="stylesheet" href="css/style.css"> 
</head>
<body>

<div class="login-background fade-in">
    <div class="login-container">

        <div class="left-panel slide-left">
            <h1><span class="bold">GMS</span> Library</h1>
            <p>
                Daftar sebagai anggota GMS Library dan dapatkan akses penuh ke koleksi buku,
                e-resources, ruang baca, dan layanan perpustakaan lainnya.
            </p>
        </div>

        <div class="right-panel slide-right">
            <h2>Register</h2>

            <?php if (isset($_SESSION['error_message'])) : ?>
                <script>
                    alert("<?= $_SESSION['error_message']; ?>");
                </script>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>


            <?php if (isset($_SESSION['alert_success'])) : ?>
                <script>alert("<?= $_SESSION['alert_success']; ?>");</script>
                <?php unset($_SESSION['alert_success']); ?>
            <?php endif; ?>


            <form action="index.php?controller=auth&action=registerProcess" method="POST">

                <label>Nama Lengkap</label>
                <input type="text" name="full_name" placeholder="Masukkan Nama Lengkap" required>

                <label>Email (Aktif)</label>
                <input type="email" name="user_email" placeholder="contoh@email.com" required>

                <label>Alamat</label>
                <input type="text" name="user_address" placeholder="Masukkan Alamat" required>

                <label>No. Telepon</label>
                <input type="text" name="user_phone" placeholder="Masukkan Nomor Telepon" required>

                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan Username" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan Password" required>

                <button type="submit" class="btn-login">Daftar</button>
            </form>
            
            <div style="margin-top: 15px; text-align: center; font-size: 0.9rem;">
                Sudah punya akun? <a href="index.php?controller=auth&action=login" style="font-weight:bold;">Login disini</a>
            </div>

        </div>

    </div>
</div>

</body>
</html>
<?php
require_once __DIR__ . "/../app/init.php";
// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;

// Cek session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?controller=user&action=showLoginForm");
    exit;
}

// Ambil data user terbaru untuk ditampilkan di form
$userModel = new User($conn);
$user = $userModel->getById($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - GMS Library</title>
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>

    <header class="navbar">
        <h2 class="logo">GMS Library</h2>
        <div class="toggle-btn"></div>
        <nav class="nav-menu">
            <ul>
                <li><a href="index.php">Beranda</a></li>

                <?php if (isset($_SESSION['role'])): ?>
                    <li><a href="booking.php">Booking</a></li>
                    <li><a href="index.php?controller=user&action=history">Riwayat</a></li>
                    <li><a href="profile.php">Profil</a></li>
                <?php endif; ?>
            </ul>

            <div class="user-action">
                <div class="icon-circle">
                    <a href="profile.php">
                        
                        <?php if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])) : ?>
                            
                            <img src="<?= $_SESSION['profile_photo'] ?>" alt="Profile" class="header-profile-img">
                        
                        <?php else : ?>
                            
                            <div class="circle"></div>
                        
                        <?php endif; ?>

                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="profile-container">

            <form class="profile-form" action="index.php?controller=user&action=updateProfile" method="POST" enctype="multipart/form-data">
                
            <div class="avatar-section">
                <div class="avatar-placeholder">
                    <?php 
                        // Cek apakah user punya foto dan filenya ada di folder uploads
                        $photoPath = 'uploads/' . ($user['user_photo'] ?? 'default.jpg');
                        
                        // Jika file tidak ditemukan di folder uploads, tampilkan icon default atau gambar default
                        if (!empty($user['user_photo']) && file_exists($photoPath) && $user['user_photo'] != 'default.jpg') : 
                    ?>
                        <img src="<?= $photoPath ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                    <?php else: ?>
                        <span style="font-size: 40px;">👤</span>
                    <?php endif; ?>

                    <label class="upload-btn">
                        <input type="file" name="user_photo" accept="image/*" hidden onchange="this.form.submit()">
                        +
                    </label>
                </div>
                <p style="font-size: 12px; color: #666; margin-top: 10px;">Ketuk '+' untuk ganti foto</p>
            </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                        value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Kosongkan jika tidak ingin mengganti password">
                </div>

                <div class="form-group">
                    <label for="user_address">Alamat</label>
                    <input type="text" id="user_address" name="user_address" 
                           value="<?= htmlspecialchars($user['user_address'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="user_phone">No. Telepon</label>
                    <input type="text" id="user_phone" name="user_phone" 
                           value="<?= htmlspecialchars($user['user_phone'] ?? '') ?>" required>
                </div>

                <div class="button-row">
                    <a href="profile.php" class="btn-cancel" style="text-decoration:none; text-align:center; padding-top:12px; box-sizing: border-box;">Batal</a>
                    
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </form>

            <?php if (isset($_SESSION['update_error'])) : ?>
                <script>
                    alert("<?= $_SESSION['update_error']; ?>");
                </script>
                <?php unset($_SESSION['update_error']); ?>
            <?php endif; ?>

        </div>
    </main>

    <footer class="footer">
        <div class="footer-left">
            <div class="h2">
                <h2>GMS Library</h2>
            </div>
            <div class="footer-left">
                <p>GMS Library adalah perpustakaan modern dengan koleksi buku dan sumber digital yang beragam. Menyediakan ruang baca nyaman, area diskusi, serta layanan peminjaman untuk mendukung belajar dan penelitian pengunjung.</p>
            </div>
        </div>

        <div class="footer-mid">
            <p><b>Navigasi</b></p>
            <p>Beranda</p>
            <p>Riwayat</p>
            <p>Profil</p>
        </div>

        <div class="footer-mid">
            <p><b>Lokasi</b></p>
            <p>Jl. Masjid Al-Furqon No.RT.10, Cepit Baru, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55283</p>
            <p><b class="kontak-title">Kontak</b></p>
            <p>email@gmslibrary.com</p>
            <p>+62 812 3456 7890</p>
        </div>
        
        <div class="footer-right">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3953.0881220390825!2d110.41220107476592!3d-7.780480992239148!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a59f1d2361f71%3A0x4a2ce83adbcfd5aa!2sPerpustakaan%20Universitas%20Atma%20Jaya%20Yogyakarta!5e0!3m2!1sid!2sid!4v1764419745591!5m2!1sid!2sid"
                width="350"
                height="250"
                style="border:0; border-radius:10px;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </footer>

</body>
</html>
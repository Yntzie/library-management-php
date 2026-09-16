<?php
session_start();
require_once __DIR__ . "/../app/init.php";
// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;

// 1. Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    // Jika belum, lempar ke halaman login
    header("Location: index.php?controller=user&action=showLoginForm");
    exit;
}

// 2. Ambil data user terbaru dari database
$userModel = new User($conn);
$user = $userModel->getById($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - GMS Library</title>
    
    <link rel="stylesheet" href="css/style.css">
    
    <link rel="stylesheet" href="css/profile.css">

    <style>
        /* Overlay Gelap di Belakang Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6); /* Hitam transparan */
            display: none; /* Sembunyi secara default */
            justify-content: center; /* Tengah Horizontal */
            align-items: center;     /* Tengah Vertikal */
            z-index: 9999;           /* Pastikan di atas segalanya */
            backdrop-filter: blur(4px); /* Efek blur estetik */
        }

        /* Kotak Putih Modal */
        .modal-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
            animation: popIn 0.3s ease-out;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .modal-box h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
            font-size: 22px;
            font-family: 'Segoe UI', sans-serif;
        }

        .modal-box p {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
        }

        /* Container Tombol di Dalam Modal */
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            width: 100%;
        }

        /* Style Dasar Tombol Modal */
        .btn-modal {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            min-width: 100px;
            transition: transform 0.2s, background 0.2s;
        }

        /* Warna Tombol Batal */
        .btn-cancel-modal {
            background: #e0e0e0;
            color: #333;
        }
        .btn-cancel-modal:hover {
            background: #d6d6d6;
        }

        /* Warna Tombol Konfirmasi (Merah) */
        .btn-confirm-modal {
            background: #8B0000;
            color: white;
        }
        .btn-confirm-modal:hover {
            background: #a30000;
        }

        /* Animasi Muncul */
        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>

<body>

    <header class="navbar">
        <h2 class="logo">GMS Library</h2>

        <div class="toggle-btn"></div>

        <nav class="nav-menu">
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
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

            <div class="avatar-section">
                <div class="avatar-placeholder">
        
                <?php 
                    // 1. Tentukan path gambar
                    $photoName = $user['user_photo'] ?? 'default.jpg';
                    $photoPath = 'uploads/' . $photoName;
                    $photoFile = __DIR__ . '/' . $photoPath;

                    // 2. Cek apakah ada datanya, filenya ada di folder, dan bukan default
                    if (!empty($photoName) && is_file($photoFile) && $photoName != 'default.jpg') :
                ?>
                    <img src="<?= htmlspecialchars($photoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Foto Profil" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                
                <?php else: ?>
                    
                    <span style="font-size: 40px;">👤</span>
                
                <?php endif; ?>

            </div>

                <div class="button-group">
                    <a href="editProfile.php" class="btn-action btn-edit">Edit Profil</a>

                    <button type="button" class="btn-action btn-logout" onclick="openLogoutModal()">
                        Keluar
                    </button>
                </div>
            </div>

            <form class="profile-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="full_name" 
                        value="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="user_address">Alamat</label>
                    <input type="text" id="user_address" 
                        value="<?= htmlspecialchars($user['user_address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="user_phone">No. Telepon</label>
                    <input type="text" id="user_phone" 
                        value="<?= htmlspecialchars($user['user_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly>
                </div>
            </form>

            <?php if (isset($_SESSION['update_success'])): ?>
                <script>
                    alert(<?= json_encode($_SESSION['update_success']); ?>);
                </script>
                <?php unset($_SESSION['update_success']); ?>
            <?php endif; ?>

        </div>
    </main>

    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box">
            <h3>Konfirmasi Keluar</h3>
            <p>Apakah Anda yakin ingin keluar dari akun?</p>
            
            <div class="modal-buttons">
                <button class="btn-modal btn-cancel-modal" onclick="closeLogoutModal()">Batal</button>
                
                <button class="btn-modal btn-confirm-modal" onclick="confirmLogout()">Ya, Keluar</button>
            </div>
        </div>
    </div>

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

    <script>
        // 1. Fungsi Buka Modal
        function openLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        // 2. Fungsi Tutup Modal
        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        // 3. Fungsi Konfirmasi (Redirect ke Logout PHP)
        function confirmLogout() {
            // Arahkan browser ke URL logout controller
            window.location.href = 'index.php?controller=auth&action=logout';
        }

        // 4. Tutup modal jika klik di area gelap (luar kotak)
        window.onclick = function(event) {
            const modal = document.getElementById('logoutModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

</body>
</html>

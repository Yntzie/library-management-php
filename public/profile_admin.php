<?php

require_once __DIR__ . "/../app/init.php";
// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'librarian' && $_SESSION['role'] !== 'admin')) {
    $_SESSION['alert_error'] = "Akses ditolak!";
    header("Location: index.php"); 
    exit;
}

// 2. Ambil data user terbaru dari database
$adminModel = new Librarian($conn);
$librarian = $adminModel->getById($_SESSION['librarian_id']);
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
            background: rgba(0, 0, 0, 0.6); 
            display: none; 
            justify-content: center;
            align-items: center;     
            z-index: 9999;           
            backdrop-filter: blur(4px); 
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
        <h2 class="logo">Admin GMS Library</h2>
        
        <div class="nav-right-wrapper" style="display: flex; align-items: center; gap: 20px;">
            
            <nav class="nav-menu">
                <ul>
                    <li><a href="indexAdmin.php">Beranda</a></li>
                    <li><a href="manajemen_buku.php?controller=book_management&action=manajemen_buku">Manajemen Buku</a></li>
                    <li><a href="pengembalian.php?controller=borrow_management&action=pengembalian">Pengembalian</a></li>
                    <li><a href="profile_admin.php?controller=borrow_management&action=profile_admin">profile</a></li>
                </ul>
            </nav>

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
        </div>
    </header>

    <main>
        <div class="profile-container">

            <div class="avatar-section">
                <div class="avatar-placeholder">
        
                <?php 
                    $photoName = $librarian['user_photo'] ?? 'default.jpg';
                    $photoPath = 'uploads/' . $photoName;

                    // 2. Cek apakah ada datanya, filenya ada di folder, dan bukan default
                    if (!empty($photoName) && file_exists($photoPath) && $photoName != 'default.jpg') : 
                ?>
                    <img src="<?= $photoPath ?>" alt="Foto Profil" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                
                <?php else: ?>
                    
                    <span style="font-size: 40px;">👤</span>
                
                <?php endif; ?>

            </div>

                <div class="button-group">
                    <button type="button" class="btn-action btn-logout" onclick="openLogoutModal()">
                        Keluar
                    </button>
                </div>
            </div>

            <form class="profile-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="full_name" 
                        value="<?= htmlspecialchars($librarian['librarian_username'] ?? '') ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="user_address">Alamat</label>
                    <input type="text" id="user_address" 
                        value="<?= htmlspecialchars($librarian['librarian_address'] ?? '') ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="user_phone">No. Telepon</label>
                    <input type="text" id="user_phone" 
                        value="<?= htmlspecialchars($librarian['librarian_phone'] ?? '') ?>" readonly>
                </div>
            </form>

            <?php if (isset($_SESSION['update_success'])): ?>
                <script>
                    alert("<?= $_SESSION['update_success']; ?>");
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
 
        function openLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function confirmLogout() {
            window.location.href = 'index.php?controller=auth&action=logout';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('logoutModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

</body>
</html>
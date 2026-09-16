<?php
require_once __DIR__ . "/../app/init.php";
// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================================
// PROTEKSI: Pastikan user sudah login dan role-nya adalah admin
// ==========================================================
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'librarian' && $_SESSION['role'] !== 'admin')) {
    $_SESSION['alert_error'] = "Akses ditolak! Anda harus login sebagai Admin.";
    header("Location: index.php"); 
    exit;
}
// JANGAN ADA KODE PHP DI DALAM BLOK IF PROTEKSI DI ATAS, KECUALI REDIRECT!

$controller = $_GET['controller'] ?? null;
$action     = $_GET['action']     ?? null;

// --- LOGIKA ROUTING (SAMA SEPERTI SEBELUMNYA) ---
if ($controller === 'auth') {
    $authController = new AuthController($conn);
    switch ($action) {
        case 'login': $authController->login(); break;
        case 'logout': $authController->logout(); break;
        case 'register': $authController->register(); break;
        case 'registerProcess': $authController->registerProcess(); break;
        case 'activate': $authController->activate(); break;
    }
    exit;
} else if ($controller === 'book') {
    $bookController = new BookController($conn);
    switch ($action) {
        case 'search': $bookController->search(); break;
        case 'create': $bookController->create(); break;
        case 'update':
        $id = $_GET['id'] ?? null; // Ambil ID dari URL
        if ($id) {
            $bookController->update($id);
        } else {
            echo json_encode(["status" => "error", "message" => "ID missing"]);
        }
        break;

        case 'delete':
        $id = $_GET['id'] ?? null; // Ambil ID dari URL
        if ($id) {
            $bookController->delete($id);
        } else {
            echo json_encode(["status" => "error", "message" => "ID missing"]);
        }
        break;
    }
    exit;
} else if ($controller === 'user') {
    $userController = new UserController($conn);
    switch ($action) {
        case 'profile': $userController->profile(); break;
        case 'updateProfile': $userController->updateProfile(); break;
        case 'showLoginForm': $userController->showLoginForm(); break;
        case 'history': $userController->history(); break;
    }
}else if ($controller === 'borrow') {
    $borrowController = new BorrowController($conn);
    switch ($action) {
        case 'createFromForm':
            $borrowController->createFromForm();
            break;
    }
    exit;
}

// ==========================================================
// PEMBERSIHAN / DEKLARASI DATA (SEHARUSNYA DI SINI)
// ==========================================================

// --- DATA DUMMY DASHBOARD (AKAN DIGANTI DENGAN DATA DATABASE) ---
$stats = [
    'total_books' => 1250,
    'total_users' => 450,
    'active_borrows' => 85,
    'overdue_books' => 12,
    'total_borrows_all_time' => 780,
    'total_returns_all_time' => 695,
];

$latestBorrows = [
    ['user' => 'Rina S.', 'book' => 'The Purpose Driven Life', 'date' => '2025-11-28'],
    ['user' => 'Joko P.', 'book' => 'Mindset', 'date' => '2025-11-27'],
    ['user' => 'Ayu L.', 'book' => 'The Monk of Mokha', 'date' => '2025-11-26'],
];


// --- LOGIKA PESAN SUKSES (POP UP) ---
$successMessage = '';
if (isset($_SESSION['alert_success'])) {
    $successMessage = $_SESSION['alert_success'];
    unset($_SESSION['alert_success']);
}

?>
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard | GMS Library</title>
    <link rel="stylesheet" href="css/style.css" /> 
    <link rel="stylesheet" href="css/admin.css" /> 
    <style>
        /* Tambahkan di sini */
        html, body {
            margin: 0;
            padding: 0;
        }

        /* Pastikan navbar fixed */
        .navbar {
            position: fixed;
            top: 0;
            width: 96%;
            z-index: 1000;
            /* ... properti lain ... */
        }

        /* CSS Khusus Dashboard Admin */
        .admin-dashboard {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            flex: 1;
            min-width: 200px;
            text-align: center;
            border-left: 5px solid #2ecc71; /* Warna hijau sukses */
        }
        .stat-card.red { border-left: 5px solid #e74c3c; } /* Merah untuk denda/terlambat */

        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
        }

        .stat-card p {
            font-size: 36px;
            font-weight: bold;
            color: #34495e;
            margin: 0;
        }

                /* Latar belakang gelap transparan */
        .popup-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Hitam transparan */
            display: flex;
            justify-content: center; /* Tengah Horizontal */
            align-items: center;     /* Tengah Vertikal */
            z-index: 9999;           /* Pastikan di paling depan */
            transition: opacity 0.5s ease; /* Animasi menghilang */
        }

        /* Kotak Pop-up */
        .popup-content {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            transform: scale(0.8);
            animation: popIn 0.3s forwards; /* Animasi muncul */
            max-width: 90%;
            width: 350px;
        }

        .popup-icon {
            font-size: 50px;
            color: #2ecc71; /* Warna Hijau Sukses */
            margin-bottom: 15px;
            display: block;
        }

        .popup-content h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-family: 'Poppins', sans-serif;
        }

        .popup-content p {
            color: #666;
            margin: 0;
            font-size: 14px;
        }

        /* Animasi Pop In */
        @keyframes popIn {
            to { transform: scale(1); }
        }

    </style>
    </head>
<body>

    <?php if (!empty($successMessage)) : ?>
        <div class="popup-overlay" id="successPopup">
            <div class="popup-content">
                <span class="popup-icon">✔</span> <h3>Berhasil!</h3>
                <p><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Tunggu 2 Detik (2000 ms)
                setTimeout(function() {
                    const popup = document.getElementById('successPopup');
                    if (popup) {
                        // Ubah opacity jadi 0 (efek fade out)
                        popup.style.opacity = '0';
                        
                        // Hapus elemen dari HTML setelah animasi selesai (500ms kemudian)
                        setTimeout(function() {
                            popup.remove();
                        }, 500);
                    }
                }, 1000); // <-- WAKTU TUNGGU DI SINI
            });
        </script>
    <?php endif; ?>

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
        
    <section class="hero">
        <img src="./asset/background.png" alt="Bookshelf" />
    </section>

    <section class="admin-dashboard">
        <h1>Dashboard Administrasi 👋</h1>
        <p>Ringkasan cepat status operasional perpustakaan Anda saat ini.</p>

        <div class="stats-grid">
            <div class="stat-card yellow">
                <h3>Total Buku</h3>
                <p><?= $stats['total_books'] ?? 0 ?></p>
            </div>
            <div class="stat-card blue">
                <h3>Total User</h3>
                <p><?= $stats['total_users'] ?? 0 ?></p>
            </div>
            
            <div class="stat-card yellow">
                <h3>Total Peminjaman</h3>
                <p><?= $stats['total_borrows_all_time'] ?? 0 ?></p>
            </div>
            
            <div class="stat-card blue">
                <h3>Total Dikembalikan</h3>
                <p><?= $stats['total_returns_all_time'] ?? 0 ?></p>
            </div>

            <div class="stat-card blue">
                <h3>Peminjaman Aktif</h3>
                <p><?= $stats['active_borrows'] ?? 0 ?></p>
            </div>
            <div class="stat-card red">
                <h3>Buku Terlambat</h3>
                <p><?= $stats['overdue_books'] ?? 0 ?></p>
            </div>
        </div>
        
        
        
    </section>

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
            <p>Manajemen Buku</p>
            <p>Manajemen User</p>
            <p>Pengembalian</p>
            <p>profile</p>     
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

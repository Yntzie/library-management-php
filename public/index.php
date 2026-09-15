<?php
require_once __DIR__ . "/../app/init.php";

// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// --- LOGIKA PESAN SUKSES (POP UP) ---
$successMessage = '';
if (isset($_SESSION['alert_success'])) {
    $successMessage = $_SESSION['alert_success'];
    unset($_SESSION['alert_success']); // Hapus session agar tidak muncul lagi saat refresh
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GMS Library</title>
    <link rel="stylesheet" href="css/style.css" />

    <style>
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
                <span class="popup-icon">&#10004;</span> <h3>Berhasil!</h3>
                <p><?= htmlspecialchars($successMessage); ?></p>
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
                }, 2000); // <-- WAKTU TUNGGU DI SINI
            });
        </script>
    <?php endif; ?>

    <header class="navbar">
        <h2 class="logo">GMS Library</h2>
        
        <div class="toggle-btn"></div>
        
        <nav class="nav-menu">
            <ul>
                <li><a href="index.php">Beranda</a></li>

                <?php if (!isset($_SESSION['role'])): ?>
                    <!-- User BELUM login -->
                    <li><a href="login.php">Login</a></li>

                <?php else: ?>
                    <!-- User SUDAH login -->
                    <?php if ($_SESSION['role'] === 'librarian'): ?>
                        <!-- Link Admin Dashboard -->
                        <li><a href="indexAdmin.php" style="color: #e74c3c; font-weight: bold;">📊 Admin</a></li>
                    <?php endif; ?>
                    
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

    <section class="hero">
        <img src="./asset/background.png" alt="Bookshelf" />
    </section>

    <?php if (isset($_SESSION['search_error'])) : ?>
        <script>
            alert("<?= $_SESSION['search_error']; ?>");
        </script>
        <?php unset($_SESSION['search_error']); ?>
    <?php endif; ?>

    <section class="search-section input">
        <div class="search-box">
            <input id="searchInput" type="text" placeholder="Cari Buku..." />
            <button id="searchBtn" type="button">Cari</button>
        </div>
    </section>

    <script src="js/search.js"></script>

    <section class="popular-section">
        <h2>Buku Terpopuler!</h2>
        <p>Buku-buku kategori terpopuler yang banyak dipinjam dalam kurun waktu 3 bulan terakhir</p>

        <div class="book-list">
            <div class="book-card">
                <img src="./asset/coverPeter.jpg" />
                <p>PETER AND THE WOLF</p>
            </div>
            <div class="book-card">
                <img src="./asset/coverWibu.jpeg" />
                <p>THE PROPHET</p>
            </div>
            <div class="book-card">
                <img src="./asset/coverPurpose.jpg" />
                <p>PURPOSE</p>
            </div>
            <div class="book-card">
                <img src="./asset/coverMonk.jpg" />
                <p>THE MONK Of MOKHA</p>
            </div>
        </div>
    </section>

    <section class="best-section">
        <h2>Buku Terbaik Tahun Ini</h2>
        <div class="best-container">
            <button class="prev-btn">❮</button>
            <div class="best-card">
                <div class="best-text">
                    <h3>MINDSET: THE NEW PSYCHOLOGY OF SUCCESS</h3>
                    <p>
                        Buku ini menjelaskan bagaimana pola pikir dapat 
                        mempengaruhi kesuksesan seseorang. Cocok untuk membaca 
                        self-improvement dan pemahaman mendalam mengenai metode 
                        berpikir yang tepat.
                    </p>
                </div>
                <div class="best-card img">
                    <img src="./asset/Best1.png" class="best-img" />
                </div>
            </div>
            <button class="next-btn">❯</button>
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
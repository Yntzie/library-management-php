<?php
require_once __DIR__ . "/../app/init.php";

// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;

// Pastikan user login
if (!isset($_SESSION['role'])) {
    header("Location: index.php?controller=auth&action=login");
    exit;
}

$bookModel = new Book($conn);

// Pencarian
$keyword = $_GET['q'] ?? null;
$books   = $keyword ? $bookModel->search($keyword) : [];

$userModel = new User($conn);
$user = $userModel->getById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GMS Library</title>
    <link rel="stylesheet" href="css/booking.css" />
</head>
<body>

<header class="navbar">
    <h2 class="logo">GMS Library</h2>
    <nav class="nav-menu">
        <ul>
            <li><a href="index.php">Beranda</a></li>
            <li><a href="booking.php">Booking</a></li>
            <li><a href="index.php?controller=user&action=history">Riwayat</a></li>
            <li><a href="profile.php">Profil</a></li>
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

<div class="form-container">
    <h2 class="form-title">Form Peminjaman Buku</h2>

    <!-- Pesan -->
    <?php if (!empty($_SESSION['success'])): ?>
        <script>
            alert("<?= addslashes($_SESSION['success']); ?>");
            // setelah OK di popup, redirect ke beranda
            window.location.href = "index.php";
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <script>
            alert("<?= addslashes($_SESSION['error']); ?>");
            // kalau error cukup stay di halaman booking
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- FORM SEARCH (GET) -->
    <div class="form-group">
        <form method="GET" action="booking.php" class="search-box">
            <input type="text" name="q" placeholder="Cari Buku..." value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit">Cari</button>
        </form>
    </div>

    <div class="divider"></div>

    <!-- FORM UTAMA (POST ke controller borrow) -->
    <form action="index.php?controller=borrow&action=createFromForm" method="POST">

        <div class="form-group">
            <label for="nama">Nama Peminjam</label>
            <input type="text" id="full_name" 
                value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" readonly>
        </div>

        <div class="form-group">
            <label for="alamat">Alamat</label>
            <input type="text" id="user_address" 
                value="<?= htmlspecialchars($user['user_address'] ?? '') ?>" readonly>
        </div>

        <div class="form-group">
            <label for="telepon">No Telepon</label>
            <input type="text" id="user_phone" 
                value="<?= htmlspecialchars($user['user_phone'] ?? '') ?>" readonly>
        </div>

        <div class="form-group">
            <label for="tgl_pinjam">Tanggal Peminjaman</label>
            <input type="date" id="tgl_pinjam" name="tgl_pinjam" class="date-readonly">
        </div>

        <div class="form-group">
            <label for="tgl_kembali">Tanggal Pengembalian</label>
            <input type="date" id="tgl_kembali" name="tgl_kembali" class="date-readonly">
        </div>

        <input type="hidden" name="librarian_id" value="1">

        <div class="book-section">
            <h3>Hasil Pencarian</h3>

            <?php if ($keyword && empty($books)): ?>
                <p style="text-align:center;">Buku tidak ditemukan.</p>
            <?php endif; ?>

            <?php foreach ($books as $b): ?>
                <div class="book-wrapper">
                    <div class="book-card">
                        <div class="book-image">
                            <img src="asset/<?= htmlspecialchars($b['cover']) ?>" width="80" height="120" alt="<?= htmlspecialchars($b['title']) ?>">
                        </div>
                        <div class="book-title"><?= htmlspecialchars($b['title']) ?></div>
                        <div class="book-author"><?= htmlspecialchars($b['author']) ?></div>
                        <div class="book-year"><?= htmlspecialchars($b['publish_year']) ?></div>
                        <div class="book-category"><?= htmlspecialchars($b['category']) ?></div>
                    </div>

                    <!-- Tombol submit di DALAM form -->
                    <button type="submit"
                            name="book_id"
                            value="<?= $b['book_id'] ?>"
                            class="submit-btn book-btn">
                        Pinjam Buku Ini
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

    </form>
</div>

<script src="js/borrow.js"></script>
</body>
</html>

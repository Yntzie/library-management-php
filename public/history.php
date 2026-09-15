<?php
// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;
// Mulai session (jika belum) agar navbar bisa baca $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Riwayat - GMS Library</title>
    <link rel="stylesheet" href="css/history.css" />
    
    <style>
        /* Badge Status */
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        /* Warna untuk status Dipinjam */
        .status-badge.dipinjam {
            background-color: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        /* Warna untuk status Dikembalikan */
        .status-badge.dikembalikan {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
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

                <?php if (isset($_SESSION['role'])): ?>
                    <li><a href="booking.php">Booking</a></li>
                    <li><a href="index.php?controller=user&action=history">Riwayat</a></li>
                    <li><a href="profile.php">Profil</a></li>
                <?php endif; ?>
            </ul>

            <div class="user-action">
                <div class="icon-circle">
                    <a href="profile.php" style="display:flex; width:100%; height:100%; align-items:center; justify-content:center;">
                        <?php if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])) : ?>
                            <img src="<?= $_SESSION['profile_photo'] ?>" alt="Profile" class="header-profile-img" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
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

    <div class="container">
        <div class="riwayat-peminjaman">
            <h1 class="title">Riwayat Peminjaman</h1>
            <p class="subtitle">Anda dapat melihat semua riwayat buku Anda</p>

            <div class="riwayat-list">

            <?php if (empty($historyData)) : ?>
                <div style="text-align: center; padding: 50px;">
                    <h3>Belum ada riwayat peminjaman.</h3>
                    <p>Ayo pinjam buku sekarang!</p>
                </div>

            <?php else : ?>
                
                <?php foreach ($historyData as $row) : ?>
                    
                    <div class="riwayat-card">
                        <div class="book-image">
                            <img src="<?= htmlspecialchars($row['final_cover'] ?? 'asset/background.png', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="object-fit:cover; width:100%; height:100%;" />
                        </div>
                        
                        <div class="book-info">
                            <div class="status-badge <?= htmlspecialchars($row['status_class'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['status_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            
                            <h3><?= htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>

                            <div class="detail">
                                <span class="label">Pengarang</span>
                                <span class="colon">:</span>
                                <span class="value"><?= htmlspecialchars($row['author'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <div class="detail">
                                <span class="label">Tahun Terbit</span>
                                <span class="colon">:</span>
                                <span class="value"><?= htmlspecialchars((string) ($row['publish_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <div class="detail">
                                <span class="label">Kategori</span>
                                <span class="colon">:</span>
                                <span class="value"><?= htmlspecialchars($row['category'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <div class="detail">
                                <span class="label2">Tanggal Peminjaman</span>
                                <span class="colon">:</span>
                                <span class="value"><?= date('d-M-Y', strtotime($row['borrow_date'])) ?></span>
                            </div>

                            <div class="detail">
                                <span class="label3">Tanggal Pengembalian</span>
                                <span class="colon">:</span>
                                <span class="value"><?= date('d-M-Y', strtotime($row['due_date'])) ?></span>
                            </div>

                            <!-- <div class="detail">
                                <span class="label">Denda</span>
                                <span class="colon">:</span>
                                <span class="value">
                                    <?= (isset($row['fine_amount']) && $row['fine_amount'] > 0) 
                                        ? "Rp " . number_format($row['fine_amount'], 0, ',', '.') 
                                        : "-" ?>
                                </span>
                            </div> -->
                        </div>
                    </div>
                
                <?php endforeach; ?>
            
            <?php endif; ?>

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

</body>
</html>

<?php
// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;

// Pastikan session dimulai jika belum, untuk menangani Navbar (Login/Logout state)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Asumsi variabel $books sudah dikirim dari Controller
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian - GMS Library</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .btn-pinjam {
            padding: 10px 20px;
            background-color: #7b0000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        .btn-pinjam:hover {
            background-color: #a00000;
        }

        .search-results-container {
            padding: 40px 20px;
            min-height: 60vh;
            /* Agar footer tidak naik ke tengah jika hasil sedikit */
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        .search-header h2 {
            color: #7b0000;
            margin: 0;
        }

        .btn-back {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            padding: 8px 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background-color: #7b0000;
            color: white;
            border-color: #7b0000;
        }

        /* Grid Layout untuk Hasil Pencarian */
        .book-grid {
            display: flex;
            flex-wrap: wrap;
            /* Ini kuncinya: agar turun ke bawah */
            gap: 25px;
            justify-content: center;
            /* Posisi di tengah */
        }

        /* Menggunakan style dasar .book-card tapi memaksa ukurannya konsisten di grid */
        .book-grid .book-card {
            width: 200px;
            /* Lebar tetap agar rapi */
            flex: 0 0 auto;
            margin: 0;
            /* Override margin bawaan css agar gap flexbox yang bekerja */
        }

        .empty-state {
            text-align: center;
            margin-top: 50px;
            color: #666;
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
                    <li><a href="history.php">Riwayat</a></li>
                    <li><a href="profile.php">Profil</a></li>
                <?php endif; ?>
            </ul>

            <div class="user-action">
                <?php if (isset($_SESSION['role'])): ?>
                    <div class="icon-circle">
                        <a href="profile.php">
                            <div class="circle"></div>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <section class="search-results-container">

        <div class="search-header">
            <h2>Hasil Pencarian</h2>
            <a href="index.php" class="btn-back">← Kembali</a>
        </div>

        <?php if (empty($books)): ?>
            <div class="empty-state">
                <h3>Buku tidak ditemukan :(</h3>
                <p>Coba gunakan kata kunci lain atau periksa ejaan Anda.</p>
            </div>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach ($books as $b): ?>
                    <!-- Kita panggil fungsi loadDetail() dengan ID buku -->
                    <div class="book-card" onclick="loadDetail('<?= $b['book_id'] ?>')">

                        <img
                            src="asset/<?= htmlspecialchars($b['cover']) ?>"
                            alt="<?= htmlspecialchars($b['title']) ?>"
                            onerror="this.onerror=null; this.src='asset/background.png';">

                        <div class="book-info">
                            <h3><?= htmlspecialchars($b['title']) ?></h3>
                            <!-- Perhatikan: sesuaikan nama kolom lain jika perlu -->
                            <p class="book-author"><?= htmlspecialchars($b['author']) ?></p>
                            <!-- Jika ingin menampilkan tahun, gunakan $b['publish_year'] -->
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </section>

    <footer class="footer">
        <div class="footer-left">
            <div class="h2">
                <h2>GMS Library</h2>
            </div>
            <div class="footer-left-desc">
                <p>GMS Library adalah perpustakaan modern dengan koleksi buku dan sumber digital yang beragam.</p>
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
            <p>Yogyakarta, Indonesia</p>
            <p><b class="kontak-title">Kontak</b></p>
            <p>email@gmslibrary.com</p>
        </div>

        <div class="footer-right">
            <div
                style="width:350px; height:250px; background:#ccc; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                [Map Area]
            </div>
        </div>
    </footer>

    <div id="bookModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>

            <!-- Di sini detail_book.php akan dimuat -->
            <div id="modal-ajax-content">
                <!-- Loading indicator default -->
                <div style="padding:50px; text-align:center;">
                    <p>Memuat data buku...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('bookModal');
        const modalContent = document.getElementById('modal-ajax-content');

        // Fungsi membuka modal & request data ke detail_book.php
        function loadDetail(id) {
            // 1. Tampilkan modal dengan state loading
            modal.style.display = "block";
            document.body.style.overflow = "hidden"; // Disable scroll background
            modalContent.innerHTML = '<div style="padding:50px; text-align:center;">Sedang mengambil data...</div>';

            // 2. Request AJAX ke detail_book.php
            fetch('detail_book.php?id=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    // 3. Masukkan hasil HTML ke dalam modal
                    modalContent.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalContent.innerHTML = '<div style="padding:20px; color:red; text-align:center;">Gagal memuat data buku.</div>';
                });
        }

        function closeModal() {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        }

        // Tutup jika klik di luar area konten
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

</body>

</html>
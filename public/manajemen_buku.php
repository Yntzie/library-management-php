<?php
// Pastikan file init.php dimuat untuk akses ke konfigurasi dan Model
require_once __DIR__ . "/../app/init.php";
// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================================
// PROTEKSI HALAMAN (Pastikan hanya Admin/Librarian yang bisa akses)
// ==========================================================
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'librarian' && $_SESSION['role'] !== 'admin')) {
    $_SESSION['alert_error'] = "Akses ditolak!";
    header("Location: index.php");
    exit;
}

// Asumsi: Kita memuat Model Book dan mengambil data
$bookModel = new Book($conn);
$books = $bookModel->getAll();

// Logika pesan sukses (jika ada)
$successMessage = '';
if (isset($_SESSION['alert_success'])) {
    $successMessage = $_SESSION['alert_success'];
    unset($_SESSION['alert_success']);
}

// Logika pesan error (jika ada)
$errorMessage = '';
if (isset($_SESSION['alert_error'])) {
    $errorMessage = $_SESSION['alert_error'];
    unset($_SESSION['alert_error']);
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Buku | GMS Library Admin</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/admin.css" />
    <style>
        /* Pastikan navbar fixed */
        .navbar {
            position: fixed;
            top: 0;
            width: 96%;
            z-index: 1000;
            /* ... properti lain ... */
        }

        /* CSS Khusus untuk halaman ini */
        .book-management-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-button {
            background-color: #2ecc71;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .table-container {
            overflow-x: auto;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: #f2f2f2;
            color: #333;
            text-transform: uppercase;
            font-size: 14px;
        }

        .data-table td .action-btn {
            padding: 5px 10px;
            margin-right: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
        }

        .action-btn.edit {
            background-color: #3498db;
            color: white;
        }

        .action-btn.delete {
            background-color: #e74c3c;
            color: white;
        }

        /* ==================================== */
        /* FORM EXPAND STYLES                   */
        /* ==================================== */
        #addBookFormContainer {
            background-color: #ffffff;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);

            /* Properti untuk efek expand/collapse */
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-in-out, padding 0.5s ease-in-out;
            padding-top: 0;
            padding-bottom: 0;
        }

        #addBookFormContainer.expanded {
            /* Nilai yang cukup besar untuk menampung form. Sesuaikan jika form lebih panjang */
            max-height: 500px;
            padding-top: 20px;
            padding-bottom: 20px;
        }

        #addBookForm form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group-full {
            grid-column: 1 / -1;
            /* Membuat elemen ini mengambil lebar penuh */
        }

        #addBookFormContainer label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        #addBookFormContainer input[type="text"],
        #addBookFormContainer input[type="number"],
        #addBookFormContainer select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .form-actions {
            grid-column: 1 / -1;
            text-align: right;
            padding-top: 10px;
        }

        .btn-submit {
            background-color: #8B0000;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-submit:hover {
            background-color: #5c0000;
        }


        /* ==================================== */
        /* TATA LETAK TABEL DAN KONTEN          */
        /* ==================================== */
        .book-management-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-button {
            background-color: #2ecc71;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }

        .table-container {
            overflow-x: auto;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: #f2f2f2;
            color: #333;
            text-transform: uppercase;
            font-size: 14px;
        }

        .data-table td .action-btn {
            padding: 5px 10px;
            margin-right: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }

        .action-btn.edit {
            background-color: #3498db;
            color: white;
        }

        .action-btn.delete {
            background-color: #e74c3c;
            color: white;
        }

        /* Media Query untuk Mobile */
        @media (max-width: 768px) {
            #addBookForm form {
                grid-template-columns: 1fr;
                /* Form menjadi satu kolom di layar kecil */
            }
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

    <div class="book-management-container">
        <h1>Manajemen Buku</h1>
        <p>Kelola semua koleksi buku perpustakaan di sini.</p>

        <?php if ($successMessage): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="header-actions">
            <!-- Tombol yang men-toggle form expand -->
            <button id="toggleAddBookForm" class="add-button">+ Tambah Buku Baru</button>
        </div>

        <script src="js/addBook.js"></script>

        <!-- Struktur Form Expand -->
        <div id="addBookFormContainer">
            <h2 id="formTitle" style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">Form Tambah Buku Baru</h2>

            <form id="addBookForm" action="indexAdmin.php?controller=book&action=create" method="POST" enctype="multipart/form-data">

                <input type="hidden" id="book_id" name="book_id">

                <div class="form-group">
                    <label for="title">Judul Buku:</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="author">Penulis:</label>
                    <input type="text" id="author" name="author" required>
                </div>

                <div class="form-group">
                    <label for="publish_year">Tahun Terbit:</label>
                    <input type="number" id="publish_year" name="publish_year" min="1900" max="<?= date('Y') ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Kategori:</label>
                    <input type="text" id="category" name="category" required>
                </div>

                <div class="form-group form-group-full">
                    <label for="cover">Cover</label>
                    <input type="file" id="cover" name="cover" accept="image/*">
                    <small id="cover-hint" style="color: #666; display: none;">Biarkan kosong jika tidak ingin mengubah cover.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="btnSave">Simpan Data</button>
                    <button type="button" id="btnCancelEdit" style="display:none; background:#95a5a6; color:white; border:none; padding:10px 20px; border-radius:5px; margin-right:10px;">Batal</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Tahun</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($book['book_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($book['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($book['author'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($book['publish_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($book['category'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($book['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <button type="button" class="action-btn edit btn-edit-trigger"
                                        data-id="<?= $book['book_id'] ?>"
                                        data-title="<?= htmlspecialchars($book['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-author="<?= htmlspecialchars($book['author'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-year="<?= htmlspecialchars((string) ($book['publish_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-category="<?= htmlspecialchars($book['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>">Edit</button>

                                    <form action="indexAdmin.php?controller=book&action=delete&id=<?= $book['book_id'] ?>"
                                        method="POST" style="display:inline;"
                                        onsubmit="return confirm('Yakin ingin menghapus buku ini?');">
                                        <button type="submit" class="action-btn delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">Tidak ada data buku.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

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

// Start output buffering to ensure header redirects can be sent
if (!ob_get_level()) {
    ob_start();
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_return') {
    $borrow_id = (int) ($_POST['borrow_id'] ?? 0);
    $book_id = (int) ($_POST['book_id'] ?? 0);
    $fine_amount = (int) ($_POST['fine_amount'] ?? 0);
    $late_days = (int) ($_POST['late_days'] ?? 0);
    $member_id = trim((string) ($_POST['member_id'] ?? ''));

    if ($borrow_id > 0) {
        $returnBookModel = new ReturnBook($conn);
        $return_date = date('Y-m-d H:i:s');

        if ($returnBookModel->returnBook($borrow_id, $return_date)) {
            $successMessage = "Pengembalian buku berhasil diproses.";
            $_SESSION['alert_success'] = $successMessage;
            header("Location: pengembalian.php?member_id=" . urlencode($member_id));
            exit;
        } else {
            $errorMessage = "Gagal memproses pengembalian buku.";
        }
    } else {
        $errorMessage = "ID peminjaman tidak valid.";
    }
}

function calculate_fine($due_date, $daily_fine_rate = 1000)
{
    $due_timestamp = strtotime($due_date);
    $today_timestamp = time();

    if ($today_timestamp <= $due_timestamp) {
        return ['days_late' => 0, 'fine_amount' => 0];
    }

    $diff_seconds = $today_timestamp - $due_timestamp;
    $days_late = floor($diff_seconds / (60 * 60 * 24));
    $fine_amount = $days_late * $daily_fine_rate;

    return ['days_late' => $days_late, 'fine_amount' => $fine_amount];
}

function format_rupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function get_active_loans_by_user_id($user_id)
{
    global $conn;

    $query = "SELECT 
        br.borrow_id, 
        br.book_id,
        u.user_id,
        u.username AS member_name, 
        bk.title AS book_title, 
        br.borrow_date, 
        br.due_date
    FROM 
        borrow br
    JOIN 
        user u ON br.user_id = u.user_id 
    JOIN 
        book bk ON br.book_id = bk.book_id
    WHERE 
        u.user_id = ?
        AND NOT EXISTS (SELECT 1 FROM return_book r WHERE r.borrow_id = br.borrow_id)
    ORDER BY br.borrow_date DESC";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("Prepare error: " . $conn->error);
        return [];
    }

    $stmt->bind_param("s", $user_id);

    if (!$stmt->execute()) {
        error_log("Execute error: " . $stmt->error);
        return [];
    }

    $result = $stmt->get_result();
    $loans = [];

    while ($row = $result->fetch_assoc()) {
        $loans[] = $row;
    }

    $stmt->close();
    return $loans;
}


$user_id_to_search = trim((string) ($_GET['member_id'] ?? ''));
$filtered_loans = [];
$total_fine = 0;
$member_name = '';

if ($user_id_to_search) {
    $filtered_loans = get_active_loans_by_user_id($user_id_to_search);

    if (!empty($filtered_loans)) {
        foreach ($filtered_loans as &$loan) { 
            $calc = calculate_fine($loan['due_date']);

            $loan['days_late'] = $calc['days_late'];
            $loan['fine_amount'] = $calc['fine_amount'];
            $loan['status_text'] = ($loan['days_late'] > 0) ? 'Terlambat' : 'Tepat Waktu';

            $total_fine += $calc['fine_amount'];
        }
        unset($loan);
        $member_name = $filtered_loans[0]['member_name'];
    }
}

if (!$successMessage) {
    $successMessage = isset($_SESSION['alert_success']) ? $_SESSION['alert_success'] : '';
}
if (!$errorMessage) {
    $errorMessage = isset($_SESSION['alert_error']) ? $_SESSION['alert_error'] : '';
}
unset($_SESSION['alert_success'], $_SESSION['alert_error']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengembalian | GMS Library Admin</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/admin.css" />
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f4f7f6;
            font-family: 'Poppins', sans-serif;
            color: #333;
            padding-top: 80px;
        }

        .navbar {
            background: #8B0000;
            /* Maroon */
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: fixed;
            top: 0;
            width: 96%;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .management-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-container,
        .summary-box {
            background-color: #ffffff;
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-form input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .btn-search {
            background-color: #8B0000;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-search:hover {
            background-color: #5c0000;
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

        .action-btn.return {
            background-color: #2ecc71;
            /* Green */
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        .action-btn.return:hover {
            background-color: #27ae60;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .status-badge.terlambat {
            background-color: #e74c3c;
        }


        .status-badge.tepat-waktu {
            background-color: #3498db;
        }

        .summary-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid #8B0000;
        }

        .summary-box h3 {
            margin: 0;
            font-size: 1.2em;
        }

        .total-fine {
            font-size: 1.5em;
            font-weight: bold;
            color: #e74c3c;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }

            .search-form input[type="text"],
            .btn-search {
                width: 100%;
            }

            .summary-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                    <li><a href="manajemen_buku.php?controller=book_management&action=manajemen_buku">Manajemen Buku</a>
                    </li>
                    <li><a href="pengembalian.php?controller=borrow_management&action=pengembalian">Pengembalian</a>
                    </li>
                    <li><a href="profile_admin.php">profile</a></li>
                </ul>
            </nav>

            <div class="user-action">
                <div class="icon-circle">
                    <?php if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])): ?>
                        <img src="<?= $_SESSION['profile_photo'] ?>" alt="Profile" class="header-profile-img">
                    <?php else: ?>
                        <div class="circle"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="management-container">
        <h1>Manajemen Pengembalian Buku</h1>
        <p>Gunakan formulir di bawah ini untuk mencari peminjam berdasarkan ID Anggota dan memproses pengembalian.</p>

        <?php if ($successMessage): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="search-container">
            <h2 style="margin-top: 0;">Cari Pinjaman Aktif</h2>
            <form class="search-form" action="pengembalian.php" method="GET">
                <input type="text" name="member_id" placeholder="Masukkan ID Anggota (Contoh: 1)"
                    value="<?= htmlspecialchars($user_id_to_search, ENT_QUOTES, 'UTF-8') ?>" required>
                <button type="submit" class="btn-search">Cari Pinjaman</button>
            </form>
        </div>

        <?php if ($user_id_to_search && empty($filtered_loans)): ?>
            <div style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                Tidak ada pinjaman aktif yang ditemukan untuk ID Anggota: <strong><?= htmlspecialchars($user_id_to_search, ENT_QUOTES, 'UTF-8') ?></strong>.
            </div>
        <?php endif; ?>

        <?php if (!empty($filtered_loans)): ?>

            <h2 style="margin-top: 30px;">
                Daftar Buku Dipinjam oleh <?= htmlspecialchars($member_name, ENT_QUOTES, 'UTF-8') ?> (ID: <?= htmlspecialchars($user_id_to_search, ENT_QUOTES, 'UTF-8') ?>)
            </h2>

            <!-- Summary Box untuk Total Denda -->
            <div class="summary-box">
                <h3>Total Denda yang Harus Dibayar:</h3>
                <span class="total-fine"><?= format_rupiah($total_fine) ?></span>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID Pinjam</th>
                            <th>Judul Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Terlambat (Hari)</th>
                            <th>Denda (Rp)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_loans as $loan): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($loan['borrow_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($loan['book_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($loan['borrow_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($loan['due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span
                                        class="status-badge <?= htmlspecialchars(strtolower(str_replace(' ', '-', $loan['status_text'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($loan['status_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td><?= htmlspecialchars((string) ($loan['days_late'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= format_rupiah($loan['fine_amount'] ?? 0) ?></td>
                                <td>
                                    <!-- Form untuk memproses pengembalian -->
                                    <form action="pengembalian.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="process_return">
                                        <input type="hidden" name="borrow_id" value="<?= htmlspecialchars((string) ($loan['borrow_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="book_id"
                                            value="<?= htmlspecialchars((string) ($loan['book_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="member_id" value="<?= htmlspecialchars($user_id_to_search, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="fine_amount" value="<?= htmlspecialchars((string) ($loan['fine_amount'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="late_days" value="<?= htmlspecialchars((string) ($loan['days_late'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="action-btn return"
                                            onclick="return confirm(<?= htmlspecialchars(json_encode('Konfirmasi pengembalian buku: ' . ($loan['book_title'] ?? '') . '? Denda: ' . format_rupiah($loan['fine_amount'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>)">
                                            Kembalikan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="footer-left">
            <div class="h2">
                <h2>GMS Library</h2>
            </div>
            <div class="footer-left">
                <p>GMS Library adalah perpustakaan modern dengan koleksi buku dan sumber digital yang beragam.
                    Menyediakan ruang baca nyaman, area diskusi, serta layanan peminjaman untuk mendukung belajar dan
                    penelitian pengunjung.</p>
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
            <p>Jl. Masjid Al-Furqon No.RT.10, Cepit Baru, Condongcatur, Kec. Depok, Kabupaten Sleman, Daerah Istimewa
                Yogyakarta 55283</p>
            <p><b class="kontak-title">Kontak</b></p>
            <p>email@gmslibrary.com</p>
            <p>+62 812 3456 7890</p>
        </div>
        <div class="footer-right">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3953.0881220390825!2d110.41220107476592!3d-7.780480992239148!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a59f1d2361f71%3A0x4a2ce83adbcfd5aa!2sPerpustakaan%20Universitas%20Atma%20Jaya%20Yogyakarta!5e0!3m2!1sid!2sid!4v1764419745591!5m2!1sid!2sid"
                width="350" height="250" style="border:0; border-radius:10px;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </footer>
</body>

</html>

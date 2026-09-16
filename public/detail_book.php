<?php

// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;
// detail_book.php
// File ini HANYA mengembalikan fragmen HTML untuk isi Modal, bukan halaman utuh.

require_once __DIR__ . "/../app/init.php";

// 2. Ambil ID dan Validasi Keamanan
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // PENTING: Gunakan Prepared Statement untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT * FROM book WHERE book_id = ?");
    $stmt->bind_param("i", $id); // "i" artinya integer
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    if ($book) {
        // Data ditemukan, siapkan variabel
        $judul = htmlspecialchars($book['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $penulis = htmlspecialchars($book['author'] ?? '', ENT_QUOTES, 'UTF-8');
        $tahun = htmlspecialchars((string) ($book['publish_year'] ?? '-'), ENT_QUOTES, 'UTF-8'); // Fallback jika null
        $sinopsis = nl2br(htmlspecialchars($book['synopsis'] ?? 'Belum ada sinopsis.', ENT_QUOTES, 'UTF-8'));
        $cover = "asset/" . htmlspecialchars($book['cover'] ?? 'background.png', ENT_QUOTES, 'UTF-8');
        
        // Output HTML yang akan disuntikkan ke dalam Modal
        ?>
        <div class="modal-body">
            <!-- BAGIAN KIRI: Informasi & Sinopsis -->
            <div class="modal-left">
                <h2><?= $judul ?></h2>
                <div class="modal-meta">
                    <p><strong>Penulis:</strong> <?= $penulis ?></p>
                    <p><strong>Tahun Terbit:</strong> <?= $tahun ?></p>
                    <!-- Tambahkan field lain jika ada, misal: Penerbit, ISBN -->
                </div>
                
                <div class="modal-desc">
                    <p><strong>Sinopsis:</strong></p>
                    <div class="synopsis-content">
                        <?= $sinopsis ?>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <!-- Tombol Aksi -->
                    <button class="btn-pinjam">Pinjam Buku Ini</button>
                </div>
            </div>

            <!-- BAGIAN KANAN: Cover -->
            <div class="modal-right">
                <img src="<?= $cover ?>" alt="Cover <?= $judul ?>">
            </div>
        </div>
        <?php
    } else {
        echo "<div style='padding:20px; text-align:center;'>Buku tidak ditemukan di database.</div>";
    }
    $stmt->close();
} else {
    echo "<div style='padding:20px; text-align:center;'>ID Buku tidak valid.</div>";
}
?>

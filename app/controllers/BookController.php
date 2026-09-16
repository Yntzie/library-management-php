<?php

class BookController
{
    private PgConnection $conn;
    private Book $bookModel;

    public function __construct(PgConnection $conn)
    {
        $this->conn = $conn;
        $this->bookModel = new Book($this->conn);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ================================
    // GET /books
    // ================================
    public function index(): void
    {
        $books = $this->bookModel->getAll();

        require BASE_PATH . '/public/book_list.php';
        return;
    }

    // ================================
    // GET /books?id=1
    // ================================
    public function show(int $book_id): void
    {
        $book = $this->bookModel->getById($book_id);

        if (!$book) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Book not found"
            ]);
            return;
        }

        echo json_encode([
            "status" => "success",
            "data" => $book
        ]);
    }

    public function search()
    {
        $keyword = trim((string) ($_GET['keyword'] ?? ''));

        $books = $this->bookModel->search($keyword);

        // Jika kosong → kirim pesan alert
        if (empty($books)) {
            $_SESSION['search_error'] = "Buku dengan kata kunci '$keyword' tidak ditemukan!";
            header("Location: index.php");
            exit;
        }

        require BASE_PATH . '/public/book_list.php';
    }

    private function coverUploadDir(): string
    {
        return BASE_PATH . '/public/uploads/';
    }


    // ================================
    // POST /books/create
    // ================================
    public function create(): void
    {
        // 1. Set Header agar JS tahu ini JSON
        header('Content-Type: application/json');

        $title = $_POST['title'] ?? null;
        $author = $_POST['author'] ?? null;
        $publish_year = $_POST['publish_year'] ?? null;
        $category = $_POST['category'] ?? null;
        $coverFile = $_FILES['cover'] ?? null;

        if (!$title || !$author || !$publish_year || !$category || !$coverFile) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Semua field wajib diisi."]);
            return;
        }

        if ($coverFile['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Gagal upload file. Error code: " . $coverFile['error']]);
            return;
        }

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExtension = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedTypes, true)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Format file tidak valid. Gunakan JPG, PNG, GIF, atau WEBP."]);
            return;
        }

        $uploadDir = $this->coverUploadDir();
        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Folder upload tidak bisa dibuat."]);
            return;
        }

        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Folder upload tidak bisa ditulis."]);
            return;
        }

        $newFileName = 'cover_' . time() . '.' . $fileExtension;
        $destination = $uploadDir . $newFileName;

        if (!move_uploaded_file($coverFile['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal memindahkan file ke folder tujuan."]);
            return;
        }

        $created = $this->bookModel->create(
            $title,
            $author,
            $publish_year,
            $category,
            $newFileName
        );

        if ($created) {
            echo json_encode([
                "status" => "success",
                "message" => "Buku berhasil ditambahkan"

            ]);
        } else {
            // Hapus file jika db gagal insert
            if (is_file($destination)) {
                unlink($destination);
            }

            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Gagal menyimpan data ke database"
            ]);
        }
        exit;
    }

    // ================================
    // POST /books/update?id=1
    // ================================
    public function update(int $book_id): void
    {
        // 1. Ambil data buku lama dari database (PENTING)
        $book = $this->bookModel->getById($book_id);

        // Jika buku tidak ditemukan
        if (!$book) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Book not found"
            ]);
            return;
        }

        // 2. Ambil data inputan Teks (Jika kosong, pakai data lama)
        $title = $_POST['title'] ?? $book['title'];
        $author = $_POST['author'] ?? $book['author'];
        $publish_year = $_POST['publish_year'] ?? $book['publish_year'];
        $category = $_POST['category'] ?? $book['category'];

        // 3. LOGIKA UPLOAD FILE (COVER)
        // Default: Gunakan cover lama
        $coverName = $book['cover'];

        // Cek apakah ada file baru yang diupload tanpa error
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {

            $uploadDir = $this->coverUploadDir();
            $fileTmp = $_FILES['cover']['tmp_name'];
            $fileName = $_FILES['cover']['name'];

            // Validasi Ekstensi
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedTypes, true)) {
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "Format file tidak valid. Gunakan JPG, PNG, GIF, atau WEBP."
                ]);
                return;
            }

            if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Folder upload tidak bisa dibuat."]);
                return;
            }

            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Folder upload tidak bisa ditulis."]);
                return;
            }

            // Generate nama unik baru
            $newCoverName = uniqid('cover_', true) . '.' . $fileExt;
            $targetPath = $uploadDir . $newCoverName;

            // Pindahkan file baru
            if (move_uploaded_file($fileTmp, $targetPath)) {
                // BERHASIL UPLOAD:

                // (Opsional) Hapus file cover lama jika ada, biar server tidak penuh
                $oldCoverPath = $uploadDir . $book['cover'];
                if (!empty($book['cover']) && is_file($oldCoverPath)) {
                    unlink($oldCoverPath);
                }

                // Update variabel coverName dengan yang baru
                $coverName = $newCoverName;

            } else {
                // Gagal upload
                http_response_code(500);
                echo json_encode([
                    "status" => "error",
                    "message" => "Gagal mengupload gambar ke server."
                ]);
                return;
            }
        }
        // Jika tidak ada file baru, $coverName tetap berisi $book['cover'] (lama)

        // 4. Update ke Database
        $updated = $this->bookModel->update(
            $book_id,
            $title,
            $author,
            $publish_year,
            $category,
            $coverName // Mengirim nama file (entah baru atau lama)
        );

        // 5. Response JSON
        if ($updated) {
            echo json_encode([
                "status" => "success",
                "message" => "Data buku berhasil diperbarui"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Gagal memperbarui database"
            ]);
        }
    }

    // ================================
    // GET /books/delete?id=1
    // ================================
    public function delete(int $book_id): void
    {
        // 1. Set Header JSON
        header('Content-Type: application/json');

        try {
            $book = $this->bookModel->getById($book_id);

            if (!$book) {
                http_response_code(404);
                echo json_encode([
                    "status"  => "error",
                    "message" => "Buku tidak ditemukan."
                ]);
                return;
            }

            // Coba langsung hapus
            // Jika ID buku ada di tabel borrow (baik sedang dipinjam atau riwayat),
            // Baris ini akan Error dan langsung loncat ke blok 'catch'
            $deleted = $this->bookModel->delete($book_id);

            if ($deleted) {
                // Hapus gambar fisik jika perlu (opsional)
                if (!empty($book['cover'])) {
                    $path = $this->coverUploadDir() . $book['cover'];
                    if (is_file($path)) unlink($path);
                }

                echo json_encode([
                    "status"  => "success",
                    "message" => "Buku berhasil dihapus permanen."
                ]);
            } else {
                // Gagal tanpa exception (jarang terjadi di delete)
                throw new Exception("Gagal menghapus data.");
            }

        } catch (PDOException $e) {
            // 2. TANGKAP ERROR FOREIGN KEY PostgreSQL (SQLSTATE 23503)
            if ($e->getCode() === '23503') {
                http_response_code(409); // Konflik data
                echo json_encode([
                    "status"  => "error",
                    "message" => "Gagal: Buku tidak bisa dihapus karena memiliki riwayat peminjaman (tercatat di database)."
                ]);
            } else {
                // Error database lainnya
                http_response_code(500);
                echo json_encode([
                    "status"  => "error",
                    "message" => "Database Error: " . $e->getMessage()
                ]);
            }
        } catch (Exception $e) {
            // Error umum
            http_response_code(500);
            echo json_encode([
                "status"  => "error",
                "message" => $e->getMessage()
            ]);
        }

        // 3. PENTING: HAPUS/JANGAN GUNAKAN header("Location: ...")
        // Biarkan JavaScript yang menangani reload jika sukses.
        exit;
    }
}

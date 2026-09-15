<?php

class BorrowController
{
    private PgConnection $conn;
    private Borrow $borrowModel;

    public function __construct(PgConnection $conn)
    {
        $this->conn        = $conn;
        $this->borrowModel = new Borrow($this->conn);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ==========================
    // API: GET semua peminjaman
    // ==========================
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = $this->borrowModel->getAll();

        echo json_encode([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    // API: detail peminjaman by id
    public function show(int $borrow_id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $borrow = $this->borrowModel->getById($borrow_id);

        if (!$borrow) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Borrow record not found',
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $borrow,
        ]);
    }

    // ==========================
    // API: create via JSON/POST body (kalau butuh API)
    // ==========================
    // OPSIONAL: API create via JSON
    public function create(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $user_id      = $input['user_id']      ?? null;
        $book_id      = $input['book_id']      ?? null;
        $librarian_id = $input['librarian_id'] ?? null;
        $borrow_date  = $input['borrow_date']  ?? null;
        $due_date     = $input['due_date']     ?? null;

        if (!$user_id || !$book_id || !$librarian_id || !$borrow_date || !$due_date) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'All fields are required'
            ]);
            return;
        }

        $ok = $this->borrowModel->create(
            (int)$user_id,
            (int)$book_id,
            (int)$librarian_id,
            $borrow_date,
            $due_date
        );

        if (!$ok) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to create borrow'
            ]);
            return;
        }

        echo json_encode([
            'status'  => 'success',
            'message' => 'Borrow created'
        ]);
    }



    // ==========================
    // FORM HTML: dari booking.php
    // route: index.php?controller=borrow&action=createFromForm
    // ==========================
    public function createFromForm(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: booking.php");
            exit;
        }

        // ⚠ user_id HARUS sesuai yang kamu set di AuthController::login()
        $user_id = (int) ($_SESSION['user_id'] ?? 0);

        $book_id      = (int) ($_POST['book_id'] ?? 0);
        $librarian_id = (int) ($_POST['librarian_id'] ?? 1);
        $borrow_date  = $_POST['tgl_pinjam']   ?? null;
        $due_date     = $_POST['tgl_kembali']  ?? null;

        if (!$user_id || !$book_id || !$borrow_date || !$due_date) {
            $_SESSION['error'] = "Data peminjaman tidak lengkap.";
            header("Location: booking.php");
            exit;
        }

        $ok = $this->borrowModel->create(
            $user_id,
            $book_id,
            $librarian_id,
            $borrow_date,
            $due_date
        );

        if ($ok) {
            $_SESSION['success'] = "Peminjaman buku berhasil.";
        } else {
            $_SESSION['error'] = "Gagal meminjam buku. Buku mungkin sudah dipinjam atau terjadi error di database.";
        }

        header("Location: booking.php");
        exit;
    }

    // ==========================
    // API: delete
    // ==========================
    public function delete(int $borrow_id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $success = $this->borrowModel->delete($borrow_id);

        if (!$success) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to delete borrow record',
            ]);
            return;
        }

        echo json_encode([
            'status'  => 'success',
            'message' => 'Borrow record deleted',
        ]);
    }

    // API: semua borrow milik 1 user
    public function getByUser(int $user_id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = $this->borrowModel->getByUser($user_id);

        if (!$data) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Borrow record not found',
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function getById(int $borrow_id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = $this->borrowModel->getById($borrow_id);

        if (!$data) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Borrow record not found',
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $data,
        ]);
    }
}

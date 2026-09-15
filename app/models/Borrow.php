<?php

class Borrow {
    private PgConnection $conn;
    private string $table = "borrow";

    public function __construct(PgConnection $conn) {
        $this->conn = $conn;
    }

    // CREATE BORROW (PINJAM BUKU)
    public function create($user_id, $book_id, $librarian_id, $borrow_date, $due_date) {
        // 1. Cek status buku di tabel book
        $sqlCheck = "SELECT status FROM book WHERE book_id = ?";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $book_id);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result()->fetch_assoc();

        if (!$result) {
            return false; // book not found
        }

        if ($result['status'] === 'DIPINJAM') {
            return false; // buku sudah dipinjam
        }

        // 2. Insert ke tabel borrow TANPA kolom status
        $sql = "INSERT INTO {$this->table} (user_id, book_id, librarian_id, borrow_date, due_date)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiss", $user_id, $book_id, $librarian_id, $borrow_date, $due_date);

        if (!$stmt->execute()) {
            // sementara buat debug
            error_log("Borrow insert error: " . $this->conn->error);
            return false;
        }

        // 3. Update status buku di tabel book
        $sqlUpdate = "UPDATE book SET status = 'DIPINJAM' WHERE book_id = ?";
        $stmtUpdate = $this->conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("i", $book_id);
        $stmtUpdate->execute();

        return true;
    }

    public function getAll() {
        $sql = "SELECT b.borrow_id, u.full_name AS user_name, bk.title, l.librarian_name, 
                       b.borrow_date, b.due_date
                FROM {$this->table} b
                JOIN user u ON b.user_id = u.user_id
                JOIN book bk ON b.book_id = bk.book_id
                JOIN librarian l ON b.librarian_id = l.librarian_id
                ORDER BY b.borrow_id DESC";

        $result = $this->conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    public function getById($borrow_id) {
        $sql = "SELECT * FROM {$this->table} WHERE borrow_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $borrow_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function delete($borrow_id) {
        $sql = "DELETE FROM {$this->table} WHERE borrow_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $borrow_id);
        return $stmt->execute();
    }

    public function getByUser($user_id) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY borrow_id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }
}

<?php

class ReturnBook {

    private PgConnection $conn;
    private string $table = "return_book";

    public function __construct(PgConnection $conn) {
        $this->conn = $conn;
    }

    // PROSES PENGEMBALIAN BUKU + OTOMATIS HITU D DENDA
    public function returnBook($borrow_id, $return_date)
    {
        // 1. Ambil due_date & book_id dari tabel borrow
        $sqlBorrow = "SELECT due_date, book_id FROM borrow WHERE borrow_id = ?";
        $stmtBorrow = $this->conn->prepare($sqlBorrow);
        $stmtBorrow->bind_param("i", $borrow_id);
        $stmtBorrow->execute();
        $borrow = $stmtBorrow->get_result()->fetch_assoc();

        if (!$borrow) {
            return false; // borrow tidak ditemukan
        }

        $due_date = $borrow['due_date'];
        $book_id  = $borrow['book_id'];

        // 2. Insert ke return_book
        $sqlReturn = "INSERT INTO {$this->table} (borrow_id, return_date)
                      VALUES (?, ?)";
        $stmtReturn = $this->conn->prepare($sqlReturn);
        $stmtReturn->bind_param("is", $borrow_id, $return_date);
        $stmtReturn->execute();

        // ambil return_id yang baru dibuat
        $return_id = $this->conn->insert_id;

        // 3. Hitung & simpan denda otomatis
        $fineModel = new Fine($this->conn);
        $fineModel->autoFine($return_id, $due_date, $return_date);

        // 4. Update status buku jadi TERSEDIA
        $sqlBook = "UPDATE book SET status = 'TERSEDIA' WHERE book_id = ?";
        $stmtBook = $this->conn->prepare($sqlBook);
        $stmtBook->bind_param("i", $book_id);
        $stmtBook->execute();

        return true;
    }

    // SEMUA DATA PENGEMBALIAN
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY return_id DESC";
        $result = $this->conn->query($sql);
        return $result->fetch_all();
    }

    // BY ID
    public function getById($return_id) {
        $sql = "SELECT * FROM {$this->table} WHERE return_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $return_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
}
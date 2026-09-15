<?php

class Fine {

    private PgConnection $conn;
    private string $table = "fine";

    public function __construct(PgConnection $conn) {
        $this->conn = $conn;
    }

    // CREATE FINE
    public function create($return_id, $late_days, $total_amount) {
        $sql = "INSERT INTO {$this->table} (return_id, late_days, total_amount)
                VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $return_id, $late_days, $total_amount);

        return $stmt->execute();
    }

    // GET ALL FINES
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY fine_id DESC";
        $result = $this->conn->query($sql);

        return $result->fetch_all();
    }

    // GET FINE BY ID
    public function getById($fine_id) {
        $sql = "SELECT * FROM {$this->table} WHERE fine_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $fine_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // GET FINE BY RETURN ID
    public function getByReturnId($return_id) {
        $sql = "SELECT * FROM {$this->table} WHERE return_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $return_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // UPDATE FINE
    public function update($fine_id, $late_days, $total_amount) {
        $sql = "UPDATE {$this->table}
                SET late_days = ?, total_amount = ?
                WHERE fine_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $late_days, $total_amount, $fine_id);

        return $stmt->execute();
    }

    // DELETE FINE
    public function delete($fine_id) {
        $sql = "DELETE FROM {$this->table} WHERE fine_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $fine_id);

        return $stmt->execute();
    }

    // =========================================
    // AUTO CALCULATE & CREATE FINE
    // =========================================
    public function autoFine($return_id, $due_date, $return_date)
    {
        // selisih detik -> hari
        $late_days = (strtotime($return_date) - strtotime($due_date)) / 86400;

        if ($late_days <= 0) {
            return false; // tidak terlambat
        }

        $late_days = floor($late_days);

        // tarif denda per hari
        $fine_per_day = 1000; // silakan ubah nominal

        $total_amount = $late_days * $fine_per_day;

        // simpan ke DB
        return $this->create($return_id, $late_days, $total_amount);
    }
}

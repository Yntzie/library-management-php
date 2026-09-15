<?php

class User {

    private PgConnection $conn;
    private string $table = "user";

    public function __construct(PgConnection $conn) {
        $this->conn = $conn;
    }

    //BONUS
    public function checkUserExists($username, $email) {
        $sql = "SELECT user_id FROM {$this->table} WHERE username = ? OR user_email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        return $stmt->num_rows > 0; // Return true jika sudah ada
    }

    // ====================================================
    // REGISTER USER (Mahasiswa)
    // ====================================================
    public function register($data) 
    {
        // Query Insert
        $sql = "INSERT INTO {$this->table} 
                (full_name, username, user_email, password, user_phone, user_address, activation_token, user_status, registration_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);

        // Binding parameter (sssssss = 7 string)
        $stmt->bind_param(
            "ssssssss",
            $data['full_name'],
            $data['username'],
            $data['user_email'],
            $data['password'], 
            $data['user_phone'],
            $data['user_address'],
            $data['activation_token'],
            $data['user_status']
        );

        return $stmt->execute();
    }

    // ====================================================
    // LOGIN USER
    // ====================================================
    public function loginUser($username, $password): array|bool|null {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) return null;

        if (password_verify($password, $user['password'])) {
            return $user; 
        }

        return null;
    }

    // ====================================================
    // GET ALL USERS (borrowers)
    // ====================================================
    public function getAll() {
        $sql = "SELECT *
                FROM {$this->table}
                ORDER BY user_id DESC";

        return $this->conn->query($sql)->fetch_all();
    }

    // ====================================================
    // GET USER BY ID
    // ====================================================
    public function getById($user_id) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // ====================================================
    // UPDATE USER PROFILE
    // ====================================================
        public function update($user_id, $username, $password, $address, $phone, $photoName) {
            // Kita update kolom 'user_photo'
            $sql = "UPDATE {$this->table}
                    SET username=?, password=?, user_address=?, user_phone=?, user_photo=?
                    WHERE user_id=?";

            $stmt = $this->conn->prepare($sql);
            
            // Bind param: ada 6 parameter sekarang (5 string, 1 integer)
            $stmt->bind_param("sssssi", $username, $password, $address, $phone, $photoName, $user_id);
            
            return $stmt->execute();
        }

    // ====================================================
    // ACTIVATE ACCOUNT
    // ====================================================
    public function activate($token) {
        // Cek dulu apakah token valid
        $checkSql = "SELECT user_id FROM {$this->table} WHERE activation_token = ? AND user_status = 'inactive'";
        $stmtCheck = $this->conn->prepare($checkSql);
        $stmtCheck->bind_param("s", $token);
        $stmtCheck->execute();
        
        if ($stmtCheck->get_result()->num_rows === 0) {
            return false; // Token tidak ditemukan atau user sudah aktif
        }

        // Update status jadi active
        $sql = "UPDATE {$this->table} SET user_status='active', activation_token=NULL 
                WHERE activation_token = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);

        return $stmt->execute();
    }

    // ====================================================
    // DELETE USER
    // ====================================================
    public function delete($user_id) {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);

        return $stmt->execute();
    }

    public function getBorrowHistory($user_id) {
        // Pastikan query ini sama persis dengan yang berhasil di phpMyAdmin
        $sql = "SELECT 
                    b.borrow_id, 
                    b.borrow_date, 
                    b.due_date,
                    bk.title, 
                    bk.author, 
                    bk.publish_year,  -- Sesuai DB
                    bk.category, 
                    bk.cover,         -- Sesuai DB
                    bk.status
                FROM borrow b
                JOIN book bk ON b.book_id = bk.book_id
                WHERE b.user_id = ?
                ORDER BY b.borrow_date DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            return $result->fetch_all();
        } else {
            return []; // Return array kosong jika gagal
        }
    }
}
?>

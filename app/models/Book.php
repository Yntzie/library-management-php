<?php
class Book {

    private PgConnection $conn;
    private string $table = "book";

    public function __construct(PgConnection $conn) {
        $this->conn = $conn;
    }

    // CREATE
    public function create($title, $author, $publish_year, $category, $cover) {
        $sql = "INSERT INTO {$this->table} (title, author, publish_year, category, cover)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssiss", $title, $author, $publish_year, $category, $cover);

        return $stmt->execute();
    }

    // READ ALL
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY book_id DESC";
        $result = $this->conn->query($sql);

        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        return $books;
    }

    // GET BY ID
    public function getById($book_id): array|bool|null {
        $sql = "SELECT * FROM {$this->table} WHERE book_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    //search by title, author, category
    public function search($keyword) {
        $sql = "SELECT * FROM book 
            WHERE title ILIKE ? OR author ILIKE ? OR category ILIKE ?";
        $key = "%$keyword%";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $key, $key, $key);
        $stmt->execute();

        $result = $stmt->get_result();

        // kalau tidak ada data, return array kosong
        if ($result->num_rows === 0) {
            return [];
        }

        return $result->fetch_all();
    }


    // UPDATE
    public function update($book_id, $title, $author, $publish_year, $category, $cover) {
        $sql = "UPDATE {$this->table} SET 
                    title=?, 
                    author=?, 
                    publish_year=?, 
                    category=?, 
                    cover=?
                WHERE book_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssissi",
            $title,
            $author,
            $publish_year,
            $category,
            $cover,
            $book_id
        );

        return $stmt->execute();
    }

    // DELETE
    public function delete($book_id) {
        $sql = "DELETE FROM {$this->table} WHERE book_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        return $stmt->execute();
    }

        // AMBIL SEMUA BUKU YANG TERSEDIA (bisa + search)
    public function getAvailable(?string $keyword = null): array {
        if ($keyword) {
            $sql = "SELECT * FROM {$this->table}
                    WHERE status = 'TERSEDIA'
                    AND (title ILIKE ? OR author ILIKE ? OR category ILIKE ?)
                    ORDER BY book_id DESC";
            $key = "%$keyword%";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sss", $key, $key, $key);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT * FROM {$this->table}
                    WHERE status = 'TERSEDIA'
                    ORDER BY book_id DESC";
            $result = $this->conn->query($sql);
        }

        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }

        return $books;
    }

}
?>

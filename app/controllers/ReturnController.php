<?php

class ReturnBookController
{
    private PgConnection $conn;
    private ReturnBook $returnModel;

    public function __construct(PgConnection $conn)
    {
        $this->conn = $conn;
        $this->returnModel = new ReturnBook($this->conn);

        // Semua response dalam bentuk JSON
        header('Content-Type: application/json; charset=utf-8');
    }

    // ==================================
    // GET: semua data pengembalian
    // route: ?controller=returnBook&action=index
    // ==================================
    public function index(): void
    {
        $data = $this->returnModel->getAll();

        echo json_encode([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    // ==================================
    // GET: detail pengembalian by return_id
    // route: ?controller=returnBook&action=show&id=1
    // ==================================
    public function show(int $return_id): void
    {
        $row = $this->returnModel->getById($return_id);

        if (!$row) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Return record not found',
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $row,
        ]);
    }

    // ==================================
    // POST: proses pengembalian buku
    // route: ?controller=returnBook&action=returnBook
    // body: borrow_id, (optional) return_date
    // ==================================
    public function returnBook(): void
    {
        // support form-data ($_POST) & raw JSON
        $input = $_POST;
        if (empty($input)) {
            $raw  = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $input = $json;
            }
        }

        $borrow_id   = $input['borrow_id']   ?? null;
        $return_date = $input['return_date'] ?? date('Y-m-d'); // default: hari ini

        if (!$borrow_id) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Field borrow_id is required',
            ]);
            return;
        }

        $success = $this->returnModel->returnBook(
            (int) $borrow_id,
            $return_date
        );

        if (!$success) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to process return (borrow not found or DB error)',
            ]);
            return;
        }

        echo json_encode([
            'status'  => 'success',
            'message' => 'Book returned successfully, status updated and fine calculated automatically (if late)',
        ]);
    }
}

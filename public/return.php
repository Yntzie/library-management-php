<?php

class FineController
{
    private PgConnection $conn;
    private Fine $fineModel;

    public function __construct(PgConnection $conn)
    {
        $this->conn = $conn;
        $this->fineModel = new Fine($this->conn);

        header('Content-Type: application/json; charset=utf-8');
    }

    // GET: semua data denda
    public function index(): void
    {
        $data = $this->fineModel->getAll();
        echo json_encode(['status' => 'success', 'data' => $data]);
    }


    // GET: detail denda by fine_id

    public function show(int $fine_id): void
    {
        $fine = $this->fineModel->getById($fine_id);
        if (!$fine) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Fine not found']);
            return;
        }
        echo json_encode(['status' => 'success', 'data' => $fine]);
    }

    // GET: denda berdasarkan return_id

    public function byReturn(int $return_id): void
    {
        $fine = $this->fineModel->getByReturnId($return_id);
        if (!$fine) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Fine for this return_id not found']);
            return;
        }
        echo json_encode(['status' => 'success', 'data' => $fine]);
    }

    public function create(): void
    {
        $input = $_POST;
        if (empty($input)) {
            $raw  = file_get_contents("php://input");
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $input = $json;
            }
        }

        $return_id    = $input['return_id']    ?? null;
        $late_days    = $input['late_days']    ?? null;
        $total_amount = $input['total_amount'] ?? null;

        if ($return_id === null || $late_days === null || $total_amount === null) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Fields return_id, late_days, and total_amount are required']);
            return;
        }

        $success = $this->fineModel->create((int)$return_id, (int)$late_days, (int)$total_amount);

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Fine created']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create fine']);
        }
    }

    // POST: update denda

    public function update(int $fine_id): void
    {
        $existing = $this->fineModel->getById($fine_id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Fine not found']);
            return;
        }

        $input = $_POST;
        if (empty($input)) {
            $raw  = file_get_contents("php://input");
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $input = $json;
            }
        }

        $late_days    = $input['late_days']    ?? $existing['late_days'];
        $total_amount = $input['total_amount'] ?? $existing['total_amount'];

        $success = $this->fineModel->update($fine_id, (int)$late_days, (int)$total_amount);

        echo json_encode([
            'status'  => $success ? 'success' : 'error',
            'message' => $success ? 'Fine updated' : 'Failed to update fine',
        ]);
    }

    // DELETE: hapus denda

    public function delete(int $fine_id): void
    {
        $success = $this->fineModel->delete($fine_id);
        echo json_encode([
            'status'  => $success ? 'success' : 'error',
            'message' => $success ? 'Fine deleted' : 'Failed to delete fine',
        ]);
    }


    // POST: auto hitung & buat denda 

    public function auto(): void
    {
        $input = $_POST;
        if (empty($input)) {
            $raw  = file_get_contents("php://input");
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $input = $json;
            }
        }

        $return_id   = $input['return_id']   ?? null;
        $due_date    = $input['due_date']    ?? null;
        $return_date = $input['return_date'] ?? null;

        if (!$return_id || !$due_date || !$return_date) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Fields return_id, due_date, and return_date are required']);
            return;
        }

        $success = $this->fineModel->autoFine((int)$return_id, $due_date, $return_date);

        if (!$success) {
            echo json_encode(['status' => 'success', 'message' => 'No fine created (not late)']);
            return;
        }

        echo json_encode(['status' => 'success', 'message' => 'Fine created automatically']);
    }
}
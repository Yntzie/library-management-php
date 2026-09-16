<?php
require_once __DIR__ . "/../app/init.php";

// Make the database connection explicit for the controllers.
$conn = $GLOBALS['conn'] ?? null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$status = '';

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_phone = trim($_POST['admin_phone'] ?? '');
    $admin_address = trim($_POST['admin_address'] ?? '');
    $safe_username = htmlspecialchars($admin_username, ENT_QUOTES, 'UTF-8');
    $safe_password = htmlspecialchars($admin_password, ENT_QUOTES, 'UTF-8');

    // Validasi input
    if (empty($admin_name) || empty($admin_username) || empty($admin_password) || empty($admin_phone) || empty($admin_address)) {
        $message = '❌ Semua field harus diisi!';
        $status = 'error';
    } else {
        // Cek apakah username sudah ada
        $librarian = new Librarian($conn);
        $stmt = $conn->prepare("SELECT librarian_id FROM librarian WHERE librarian_username = ?");
        $stmt->bind_param("s", $admin_username);
        $stmt->execute();
        $existing = $stmt->get_result();
        
        if ($existing->num_rows > 0) {
            $message = "❌ Username '$safe_username' sudah terdaftar!";
            $status = 'error';
        } else {
            // Buat admin baru
            if ($librarian->register($admin_name, $admin_username, $admin_password, 'ADMIN', $admin_phone, $admin_address)) {
                $message = "✅ Admin berhasil dibuat!<br>
                           Username: <strong>$safe_username</strong><br>
                           Password: <strong>$safe_password</strong><br>
                           <br>⚠️ <strong>PENTING:</strong> Hapalkan password ini dan hapus file create_admin.php untuk keamanan!";
                $status = 'success';
            } else {
                $message = '❌ Gagal membuat admin. Error: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
                $status = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Admin - GMS Library</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            color: #34495e;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #4ade80;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
            font-weight: 600;
            font-size: 14px;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ecf0f1;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        button {
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            font-size: 16px;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .warning {
            background: #fef08a;
            border: 1px solid #eab308;
            color: #713f12;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>🔐 Buat Akun Admin</h1>
        <p class="subtitle">Isi form di bawah untuk membuat akun administrator GMS Library</p>

        <div class="warning">
            ⚠️ <strong>PERHATIAN:</strong> File ini seharusnya DIHAPUS setelah admin berhasil dibuat untuk alasan keamanan!
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $status ?> show">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="admin_name">Nama Admin *</label>
                <input type="text" id="admin_name" name="admin_name" placeholder="Contoh: Admin Perpustakaan" required>
            </div>

            <div class="form-group">
                <label for="admin_username">Username *</label>
                <input type="text" id="admin_username" name="admin_username" placeholder="Contoh: admin123" required>
            </div>

            <div class="form-group">
                <label for="admin_password">Password *</label>
                <input type="password" id="admin_password" name="admin_password" placeholder="Masukkan password yang kuat" required>
            </div>

            <div class="form-group">
                <label for="admin_phone">Nomor Telepon *</label>
                <input type="tel" id="admin_phone" name="admin_phone" placeholder="Contoh: 081234567890" required>
            </div>

            <div class="form-group">
                <label for="admin_address">Alamat *</label>
                <textarea id="admin_address" name="admin_address" placeholder="Masukkan alamat" rows="3" required></textarea>
            </div>

            <button type="submit">✓ Buat Admin</button>
        </form>

        <div class="links">
            <a href="login.php">← Kembali ke Login</a>
        </div>
    </div>

</body>
</html>

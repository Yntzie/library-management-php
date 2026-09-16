<?php

class UserController
{
    private PgConnection $conn;
    private User $userModel;

    public function __construct(PgConnection $conn)
    {
        $this->conn = $conn;
        $this->userModel = new User($this->conn);
    }

    // ====================================================
    // LIST SEMUA USER (untuk admin)
    // ====================================================
    public function index()
    {
        header("Location: indexAdmin.php");
        exit;
    }

    // ====================================================
    // HALAMAN REGISTER FORM
    // ====================================================
    public function showRegisterForm()
    {
        require BASE_PATH . '/public/register.php';
    }

    // ====================================================
    // PROSES REGISTER
    // ====================================================
    public function register()
    {
        $name     = $_POST['name']     ?? null;
        $address  = $_POST['address']  ?? null;
        $phone    = $_POST['phone']    ?? null;
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$name || !$address || !$phone || !$username || !$password) {
            // validasi sederhana
            echo "<script>alert('Semua field wajib diisi'); history.back();</script>";
            return;
        }

        // token aktivasi acak
        $activation_token = bin2hex(random_bytes(16));

        $data = [
            "full_name"        => $name,
            "username"         => $username,
            "user_email"       => $_POST['email'], // pastikan ada di form
            "password"         => password_hash($password, PASSWORD_DEFAULT),
            "user_phone"       => $phone,
            "user_address"     => $address,
            "activation_token" => $activation_token,
            "user_status"      => "inactive"
        ];

        $success = $this->userModel->register($data);


        if ($success) {
            // di sini nanti bisa kirim email aktivasi pakai token
            // contoh link: index.php?controller=user&action=activate&token=xxxx

            echo "<script>alert('Registrasi berhasil! Silakan cek email untuk aktivasi akun.'); window.location='index.php?controller=user&action=showLoginForm';</script>";
        } else {
            echo "<script>alert('Registrasi gagal'); history.back();</script>";
        }
    }

    // ====================================================
    // HALAMAN LOGIN FORM
    // ====================================================
    public function showLoginForm()
    {
        header("Location: login.php");
        exit;
    }

    // ====================================================
    // PROSES LOGIN
    // ====================================================
    public function login()
    {
        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$username || !$password) {
            echo "<script>alert('Username dan password wajib diisi'); history.back();</script>";
            return;
        }

        $user = $this->userModel->loginUser($username, $password);

        if (!$user) {
            echo "<script>alert('Username atau password salah'); history.back();</script>";
            return;
        }

        if (($user['user_status'] ?? '') !== 'active') {
            echo "<script>alert('Akun belum aktif. Silakan aktivasi terlebih dahulu.'); history.back();</script>";
            return;
        }

        // set session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'] ?? $user['username'];
        $_SESSION['username']  = $user['username'];

        // redirect ke halaman home / dashboard
        header("Location: index.php");
        exit;
    }

    // ====================================================
    // LOGOUT
    // ====================================================
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        header("Location: index.php");
        exit;
    }

    // ====================================================
    // PROFIL USER LOGIN
    // ====================================================
    public function profile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?controller=user&action=showLoginForm");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $user    = $this->userModel->getById($user_id);

        header("Location: profile.php");
        exit;
    }

    // ====================================================
    // UPDATE PROFIL (POST)
    // ====================================================
    public function updateProfile()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php"); exit;
        }

        $user_id = $_SESSION['user_id'];
        $oldData = $this->userModel->getById($user_id);

        if (!$oldData) {
            $_SESSION['update_error'] = "Data user tidak ditemukan.";
            header("Location: profile.php");
            exit;
        }

        // 1. LOGIC PASSWORD
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $oldData['password'];

        // 2. LOGIC UPLOAD FOTO (Sesuaikan dengan kolom user_photo)
        $photoName = $oldData['user_photo']; // Default pakai yang lama
        $uploadDir = BASE_PATH . '/public/uploads/';

        // Cek apakah ada file yang diupload di input bernama 'user_photo'
        if (isset($_FILES['user_photo']) && $_FILES['user_photo']['error'] === 0) {
            $fileType  = strtolower(pathinfo($_FILES["user_photo"]["name"], PATHINFO_EXTENSION));
            $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileType, $allowed, true)) {
                if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
                    $_SESSION['update_error'] = "Folder upload tidak bisa dibuat.";
                    header("Location: profile.php"); exit;
                }

                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    $_SESSION['update_error'] = "Folder upload tidak bisa ditulis.";
                    header("Location: profile.php"); exit;
                }

                // Nama file unik: IDUser_Timestamp.jpg
                $newFileName = $user_id . '_' . time() . '.' . $fileType;
                
                if (move_uploaded_file($_FILES["user_photo"]["tmp_name"], $uploadDir . $newFileName)) {
                    $photoName = $newFileName; // Update nama file
                    
                    // Update session agar foto di navbar langsung berubah
                    $_SESSION['profile_photo'] = 'uploads/' . $newFileName;
                } else {
                    $_SESSION['update_error'] = "Gagal mengupload foto profil.";
                    header("Location: profile.php"); exit;
                }
            } else {
                $_SESSION['update_error'] = "Format file tidak didukung (hanya JPG, PNG, GIF, WEBP).";
                header("Location: profile.php"); exit;
            }
        }

        // 3. DATA LAIN
        $name    = $_POST['username']     ?: $oldData['username'];
        $address = $_POST['user_address'] ?: $oldData['user_address'];
        $phone   = $_POST['user_phone']   ?: $oldData['user_phone'];

        // 4. UPDATE DATABASE (Kirim $photoName ke model)
        $success = $this->userModel->update($user_id, $name, $password, $address, $phone, $photoName);

        if ($success) {
            $_SESSION['update_success'] = "Profil berhasil diupdate!";
        } else {
            $_SESSION['update_error'] = "Gagal update profil.";
        }

        header("Location: profile.php");
        exit;
    }

    public function getUser() {
        if (!isset($_SESSION['user_id'])) return null;
        return $this->userModel->getById($_SESSION['user_id']);
    }


    // ====================================================
    // AKTIVASI AKUN VIA TOKEN
    // link: index.php?controller=user&action=activate&token=xxxxx
    // ====================================================
    public function activate()
    {
        $token = $_GET['token'] ?? null;

        if (!$token) {
            echo "<script>alert('Token tidak valid'); window.location='index.php';</script>";
            return;
        }

        $success = $this->userModel->activate($token);

        if ($success) {
            echo "<script>alert('Akun berhasil diaktivasi. Silakan login.'); window.location='index.php?controller=user&action=showLoginForm';</script>";
        } else {
            echo "<script>alert('Aktivasi gagal atau token tidak valid'); window.location='index.php';</script>";
        }
    }

    // ====================================================
    // HAPUS USER (untuk admin)
    // ====================================================
    public function delete()
    {
        $user_id = $_GET['id'] ?? null;

        if (!$user_id) {
            echo "<script>alert('User ID tidak ditemukan'); history.back();</script>";
            return;
        }

        $success = $this->userModel->delete($user_id);

        if ($success) {
            echo "<script>alert('User berhasil dihapus'); window.location='index.php?controller=user&action=index';</script>";
        } else {
            echo "<script>alert('Gagal menghapus user'); history.back();</script>";
        }
    }

    // File: app/controllers/UserController.php

    public function history() {
        // 1. Cek Login
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?controller=user&action=showLoginForm");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        
        // 2. Ambil Data Mentah dari Model
        $historyData = $this->userModel->getBorrowHistory($user_id);

        // 3. PROSES DATA (Logika Tampilan dipindah ke sini)
        // Kita loop data mentah dan tambahkan data yang siap tampil
        $publicPath = defined('BASE_PATH') ? BASE_PATH . '/public' : dirname(__DIR__, 2) . '/public';

        foreach ($historyData as $key => $row) {
            
            // --- A. LOGIKA STATUS ---
            $statusDB = strtoupper($row['status'] ?? ''); 

            if ($statusDB == 'DIPINJAM') {
                $historyData[$key]['status_label'] = "Dipinjam";
                $historyData[$key]['status_class'] = "dipinjam";
            } else {
                $historyData[$key]['status_label'] = "Dikembalikan";
                $historyData[$key]['status_class'] = "dikembalikan";
            }

            // --- B. LOGIKA GAMBAR ---
            $folderAsset = 'asset/'; 
            $namaCover   = $row['cover'] ?? '';
            $pathCover   = $folderAsset . $namaCover;
            $fileCover   = $publicPath . '/' . $pathCover;

            // Cek fisik file di folder public, karena runtime Vercel masuk lewat /api.
            if (!empty($namaCover) && is_file($fileCover)) {
                $historyData[$key]['final_cover'] = $pathCover;
            } else {
                // Fallback ke default
                $historyData[$key]['final_cover'] = 'asset/background.png'; 
            }
        }

        require $publicPath . '/history.php';
        exit;
    }
}

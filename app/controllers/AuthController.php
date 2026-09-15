<?php

require_once __DIR__ . '/../helpers/mailer.php';
// require_once 'helpers/utils.php';

class AuthController
{
    private PgConnection $conn;
    private User $userModel;
    private Librarian $librarianModel;

    public function __construct(PgConnection $conn)
    {
        $this->conn           = $conn;
        $this->userModel      = new User($this->conn);
        $this->librarianModel = new Librarian($this->conn);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Jika bukan POST, tampilkan form login
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once 'login.php';
            return;
        }

        $username = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        // ============================================
        // 1. Cek login sebagai LIBRARIAN
        // ============================================
        $lib = $this->librarianModel->loginLib($username, $password);
        
        if ($lib) {
            $_SESSION['role']               = 'librarian';
            $_SESSION['librarian_id']       = $lib['librarian_id'];
            $_SESSION['librarian_name']     = $lib['librarian_name'] ?? null;
            $_SESSION['librarian_username'] = $lib['librarian_username'] ?? $username;
            $_SESSION['librarian_role']     = $lib['librarian_role'] ?? 'STAFF';

            $foto = $user['user_photo'] ?? ''; 

            if (!empty($foto) && $foto != 'default.jpg') {
                // Simpan path gambar ke session
                // Sesuaikan 'uploads/' dengan lokasi folder upload kamu
                $_SESSION['profile_photo'] = 'uploads/' . $foto; 
            } else {
                // Jika tidak punya foto, kosongkan session
                $_SESSION['profile_photo'] = ''; 
            }

            $_SESSION['alert_success'] = "Selamat Datang, Librarian " . ($lib['librarian_name'] ?? $username);

            // Arahkan ke admin dashboard
            header("Location: indexAdmin.php");
            exit;
        }

        // ============================================
        // 2. Cek login sebagai USER
        // ============================================
        $user = $this->userModel->loginUser($username, $password);

        if ($user) {

            // Cek aktivasi
            if ($user['user_status'] !== 'active') {
                $_SESSION['error_message'] = "Akun belum diaktivasi. Cek email Anda.";
                header("Location: index.php?controller=auth&action=login");
                exit;
            }

            $_SESSION['role']      = 'user';
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'] ?? $username;
            $_SESSION['user_name'] = $user['full_name'];

            $foto = $user['user_photo'] ?? ''; 

            if (!empty($foto) && $foto != 'default.jpg') {
                // Simpan path gambar ke session
                // Sesuaikan 'uploads/' dengan lokasi folder upload kamu
                $_SESSION['profile_photo'] = 'uploads/' . $foto; 
            } else {
                // Jika tidak punya foto, kosongkan session
                $_SESSION['profile_photo'] = ''; 
            }

            $_SESSION['alert_success'] = "Selamat Datang, " . ($user['username'] ?? $username);

            header("Location: index.php");
            exit;
        }

        // ============================================
        // 3. Jika login gagal
        // ============================================
        $_SESSION['error_message'] = "Username atau password salah.";

        // FIX: gunakan routing sesuai sistem
        header("Location: index.php?controller=auth&action=login");
        exit;
    }


    public function register()
    {
        require BASE_PATH . '/public/register.php';
    }

    // ====================================================
    // 3. PROSES REGISTER
    // ====================================================
    public function registerProcess()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require BASE_PATH . '/public/register.php'; 
            return;
        }

        $nama     = trim($_POST['full_name'] ?? ''); 
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['user_email'] ?? '');
        $phone    = trim($_POST['user_phone'] ?? '');
        $address  = trim($_POST['user_address'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validasi input wajib
        if (empty($nama) || empty($username) || empty($email) || empty($password)) {
            $_SESSION['error_message'] = "Nama, Username, Email, dan Password wajib diisi!";
            header("Location: index.php?controller=auth&action=register");
            exit;
        }

        // Cek Duplikat
        if ($this->userModel->checkUserExists($username, $email)) {
            $_SESSION['error_message'] = "Username atau Email sudah terdaftar!";
            header("Location: index.php?controller=auth&action=register");
            exit;
        }

        $token = bin2hex(random_bytes(32)); 
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $data = [
            'full_name'        => $nama,
            'username'         => $username,
            'user_email'       => $email,
            'password'         => $hashed_password,
            'user_phone'       => $phone,
            'user_address'     => $address,
            'user_status'      => 'inactive',
            'activation_token' => $token
        ];

        // Simpan ke Database
        if ($this->userModel->register($data)) {
            
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $folder = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

            $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $folder . "/";


            $link = $base_url . "index.php?controller=auth&action=activate&token=" . $token;

            $subject = "Aktivasi Akun GMS Library";
            $message = "Halo $nama, klik link berikut untuk aktivasi akun:<br>
                        <a href='$link'>Aktifkan Akun</a>";

            // TRY - CATCH SUPAYA TIDAK MATI
            try {
                $kirim = Mailer::sendEmail($email, $subject, $message);
            } catch (Exception $e) {
                $kirim = false;
            }

            if ($kirim) {
                $_SESSION['alert_success'] = "Registrasi Berhasil! Silakan cek email untuk aktivasi.";
            } else {
                $_SESSION['alert_success'] = "Registrasi berhasil, tetapi email gagal dikirim.";
            }

            // PENTING: redirect ke REGISTER biar alert tampil di register.php
            header("Location: index.php?controller=auth&action=register");
            exit;

        } else {
            $_SESSION['error_message'] = "Gagal mendaftar. Silakan coba lagi.";
            header("Location: index.php?controller=auth&action=register");
            exit;
        }
    }


    public function activate()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $token = $_GET['token'] ?? '';

        if ($this->userModel->activate($token)) {
            $_SESSION['alert_success'] = "Akun berhasil diaktifkan! Silakan login.";
        } else {
            $_SESSION['alert_success'] = "Link aktivasi tidak valid atau akun sudah aktif."; 
        }

        header("Location: index.php?controller=auth&action=login");
        exit;
    }

    public function logout()
    {
        session_unset();
        session_destroy();

        header("Location: index.php");
        exit;
    }
}

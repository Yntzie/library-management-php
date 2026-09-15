# GMS Library

GMS Library adalah aplikasi perpustakaan berbasis PHP native untuk mengelola katalog buku, peminjaman, pengembalian, profil anggota, dan dashboard admin. Aplikasi ini memakai PostgreSQL sebagai database, Composer untuk dependency, PHPMailer untuk email aktivasi, dan dotenv untuk konfigurasi aplikasi.

## Fitur Utama

- Registrasi dan login anggota.
- Aktivasi akun anggota melalui email.
- Login petugas/admin perpustakaan.
- Pencarian buku berdasarkan judul, penulis, atau kategori.
- Booking/peminjaman buku oleh anggota.
- Riwayat peminjaman anggota.
- Manajemen buku oleh admin, termasuk tambah, edit, hapus, dan upload cover.
- Manajemen pengembalian buku oleh admin.
- Perhitungan denda otomatis untuk keterlambatan, default Rp1.000 per hari.
- Profil anggota dan profil admin.

## Teknologi

- PHP native
- PostgreSQL
- Composer
- PHPMailer
- vlucas/phpdotenv
- HTML, CSS, dan JavaScript

## Struktur Folder

```text
TubesPWD/
+-- app/
|   +-- config/          # Konfigurasi database
|   +-- controllers/     # Controller aplikasi
|   +-- helpers/         # Helper umum dan mailer
|   +-- models/          # Model database
|   +-- init.php         # Bootstrap aplikasi
+-- public/
|   +-- api/             # Endpoint API kecil
|   +-- asset/           # Asset statis bawaan
|   +-- css/             # Stylesheet
|   +-- js/              # Script frontend
|   +-- uploads/         # File upload cover/profil
|   +-- index.php        # Entry point user
+-- vendor/              # Dependency Composer
+-- .env.example         # Contoh konfigurasi environment
+-- composer.json
+-- README.md
```

## Prasyarat

Pastikan environment lokal sudah memiliki:

- PHP 8.0 atau lebih baru
- PostgreSQL
- Ekstensi PHP `pdo_pgsql`
- Composer
- Web server lokal seperti XAMPP, Laragon, atau PHP built-in server

## Instalasi

1. Clone atau salin proyek ini ke folder web server.

   Contoh XAMPP:

   ```bash
   C:\xampp\htdocs\TubesPWD
   ```

2. Masuk ke folder proyek.

   ```bash
   cd TubesPWD
   ```

3. Install dependency Composer.

   ```bash
   composer install
   ```

4. Buat database PostgreSQL untuk aplikasi.

   ```sql
   CREATE DATABASE nama_database;
   ```

5. Salin file environment.

   ```bash
   cp .env.example .env
   ```

   Jika memakai Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

6. Isi konfigurasi database dan SMTP di `.env`.

   ```env
   DB_HOST=
   DB_USER=
   DB_PASS=
   DB_NAME=
   DB_PORT=

   MAIL_HOST=
   MAIL_USERNAME=
   MAIL_PASSWORD=
   MAIL_FROM=
   MAIL_FROM_NAME=
   ```

   Isi nilainya sesuai konfigurasi database dan SMTP lokal/server yang digunakan.

## Setup Database

Saat aplikasi dijalankan, migration otomatis akan membuat tabel `schema_migrations` dan tabel utama aplikasi jika belum ada. Migration dipanggil dari `app/init.php`, sehingga cukup pastikan database PostgreSQL sudah dibuat dan konfigurasi `.env` sudah benar.

Migration utama membuat tabel berikut:

- `schema_migrations`
- `"user"`
- `librarian`
- `book`
- `"borrow"`
- `return_book`
- `fine`

Schema PostgreSQL yang dibuat migration:

```sql
CREATE TABLE "user" (
  user_id SERIAL PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  user_email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  user_phone VARCHAR(20),
  user_address TEXT,
  user_photo VARCHAR(255) DEFAULT 'default.jpg',
  activation_token VARCHAR(255),
  user_status VARCHAR(20) DEFAULT 'inactive' CHECK (user_status IN ('active', 'inactive')),
  registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE librarian (
  librarian_id SERIAL PRIMARY KEY,
  librarian_name VARCHAR(100) NOT NULL,
  librarian_username VARCHAR(50) NOT NULL UNIQUE,
  librarian_password VARCHAR(255) NOT NULL,
  librarian_role VARCHAR(50) DEFAULT 'STAFF',
  librarian_phone VARCHAR(20),
  librarian_address TEXT,
  librarian_status VARCHAR(20) DEFAULT 'ACTIVE',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE book (
  book_id SERIAL PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  author VARCHAR(100) NOT NULL,
  publish_year INT NOT NULL,
  category VARCHAR(100) NOT NULL,
  cover VARCHAR(255),
  status VARCHAR(20) DEFAULT 'TERSEDIA' CHECK (status IN ('TERSEDIA', 'DIPINJAM'))
);

CREATE TABLE "borrow" (
  borrow_id SERIAL PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  librarian_id INT NOT NULL,
  borrow_date DATE NOT NULL,
  due_date DATE NOT NULL,
  FOREIGN KEY (user_id) REFERENCES "user"(user_id),
  FOREIGN KEY (book_id) REFERENCES book(book_id),
  FOREIGN KEY (librarian_id) REFERENCES librarian(librarian_id)
);

CREATE TABLE return_book (
  return_id SERIAL PRIMARY KEY,
  borrow_id INT NOT NULL,
  return_date TIMESTAMP NOT NULL,
  FOREIGN KEY (borrow_id) REFERENCES "borrow"(borrow_id)
);

CREATE TABLE fine (
  fine_id SERIAL PRIMARY KEY,
  return_id INT NOT NULL,
  late_days INT NOT NULL DEFAULT 0,
  total_amount INT NOT NULL DEFAULT 0,
  FOREIGN KEY (return_id) REFERENCES return_book(return_id)
);
```

Catatan: tabel user ditulis sebagai `"user"` karena `user` adalah nama khusus di PostgreSQL.

## Menjalankan Aplikasi

### Opsi 1: PHP Built-in Server

Jalankan dari root proyek:

```bash
php -S localhost:8000 -t public
```

Buka aplikasi di browser:

```text
http://localhost:8000
```

### Opsi 2: XAMPP

1. Letakkan folder proyek di `htdocs`.
2. Jalankan Apache dan PostgreSQL.
3. Buka:

   ```text
   http://localhost/TubesPWD/public/
   ```

## Membuat Akun Admin

Setelah database siap, buka halaman berikut:

```text
http://localhost:8000/create_admin.php
```

Atau jika memakai XAMPP:

```text
http://localhost/TubesPWD/public/create_admin.php
```

Isi form admin, lalu login melalui halaman login. Untuk keamanan, hapus atau nonaktifkan `public/create_admin.php` setelah akun admin berhasil dibuat.

## Halaman Penting

- `public/index.php` - beranda user dan routing utama user.
- `public/login.php` - halaman login.
- `public/register.php` - halaman registrasi.
- `public/booking.php` - form peminjaman buku.
- `public/history.php` - riwayat peminjaman user.
- `public/profile.php` - profil user.
- `public/indexAdmin.php` - dashboard admin.
- `public/manajemen_buku.php` - manajemen data buku.
- `public/pengembalian.php` - manajemen pengembalian dan denda.
- `public/profile_admin.php` - profil admin.

## Alur Singkat Penggunaan

1. User melakukan registrasi.
2. Sistem mengirim email aktivasi.
3. User membuka link aktivasi, lalu login.
4. User mencari buku dan melakukan booking/peminjaman.
5. Admin mengelola buku dari dashboard admin.
6. Admin memproses pengembalian buku.
7. Sistem menghitung denda otomatis jika buku terlambat dikembalikan.

## Konfigurasi Upload

File cover buku dan foto profil disimpan di:

```text
public/uploads/
```

Pastikan folder tersebut dapat ditulis oleh web server. Jika folder belum ada, buat manual atau biarkan aplikasi membuatnya saat upload cover buku.

## Troubleshooting

### Database connection error

Periksa `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT` di `.env`, lalu pastikan service PostgreSQL berjalan dan ekstensi PHP `pdo_pgsql` aktif.

### Email aktivasi tidak terkirim

Periksa isi `.env`, terutama `MAIL_HOST`, `MAIL_USERNAME`, dan `MAIL_PASSWORD`. Jika memakai Gmail, gunakan app password, bukan password akun biasa.

### Halaman admin tidak bisa diakses

Pastikan login memakai akun librarian/admin. Buat akun admin melalui `create_admin.php` jika belum ada.

### Upload cover gagal

Pastikan folder `public/uploads` ada dan punya permission tulis. Periksa juga ukuran upload maksimum di konfigurasi PHP (`upload_max_filesize` dan `post_max_size`).

## Catatan Keamanan

- Jangan commit file `.env` ke repository publik.
- Simpan kredensial database dan SMTP hanya di `.env`.
- Hapus atau nonaktifkan `public/create_admin.php` setelah admin dibuat.
- Gunakan password SMTP/app password yang aman.
- Jangan tampilkan error database detail di production.

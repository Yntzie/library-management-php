<?php

function runMigrations(PgConnection $conn): void
{
  $pdo = $conn->pdo();

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS schema_migrations (
      version VARCHAR(100) PRIMARY KEY,
      migrated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
  ");

  $migrations = [
    '202609160001_create_library_tables' => [
      <<<SQL
      CREATE TABLE IF NOT EXISTS "user" (
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
      )
      SQL,
      <<<SQL
      CREATE TABLE IF NOT EXISTS librarian (
        librarian_id SERIAL PRIMARY KEY,
        librarian_name VARCHAR(100) NOT NULL,
        librarian_username VARCHAR(50) NOT NULL UNIQUE,
        librarian_password VARCHAR(255) NOT NULL,
        librarian_role VARCHAR(50) DEFAULT 'STAFF',
        librarian_phone VARCHAR(20),
        librarian_address TEXT,
        librarian_status VARCHAR(20) DEFAULT 'ACTIVE',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
      SQL,
      <<<SQL
      CREATE TABLE IF NOT EXISTS book (
        book_id SERIAL PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        author VARCHAR(100) NOT NULL,
        publish_year INT NOT NULL,
        category VARCHAR(100) NOT NULL,
        cover VARCHAR(255),
        status VARCHAR(20) DEFAULT 'TERSEDIA' CHECK (status IN ('TERSEDIA', 'DIPINJAM'))
      )
      SQL,
      <<<SQL
      CREATE TABLE IF NOT EXISTS "borrow" (
        borrow_id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES "user"(user_id),
        book_id INT NOT NULL REFERENCES book(book_id),
        librarian_id INT NOT NULL REFERENCES librarian(librarian_id),
        borrow_date DATE NOT NULL,
        due_date DATE NOT NULL
      )
      SQL,
      <<<SQL
      CREATE TABLE IF NOT EXISTS return_book (
        return_id SERIAL PRIMARY KEY,
        borrow_id INT NOT NULL REFERENCES "borrow"(borrow_id),
        return_date TIMESTAMP NOT NULL
      )
      SQL,
      <<<SQL
      CREATE TABLE IF NOT EXISTS fine (
        fine_id SERIAL PRIMARY KEY,
        return_id INT NOT NULL REFERENCES return_book(return_id),
        late_days INT NOT NULL DEFAULT 0,
        total_amount INT NOT NULL DEFAULT 0
      )
      SQL,
      <<<SQL
      CREATE INDEX IF NOT EXISTS idx_book_status ON book(status)
      SQL,
      <<<SQL
      CREATE INDEX IF NOT EXISTS idx_borrow_user_id ON "borrow"(user_id)
      SQL,
      <<<SQL
      CREATE INDEX IF NOT EXISTS idx_return_book_borrow_id ON return_book(borrow_id)
      SQL,
    ],
    '202609160002_seed_default_admin' => [
      <<<SQL
      INSERT INTO librarian (
        librarian_id,
        librarian_name,
        librarian_username,
        librarian_password,
        librarian_role,
        librarian_phone,
        librarian_address,
        librarian_status
      )
      SELECT
        1,
        'Administrator',
        CASE
          WHEN EXISTS (SELECT 1 FROM librarian WHERE librarian_username = 'admin')
          THEN 'admin_' || FLOOR(EXTRACT(EPOCH FROM NOW()))::TEXT
          ELSE 'admin'
        END,
        '\$2y\$12\$rP3go8Fool.uSflcsSep9uAfXnS6M7d27XtZopgVmJgEund3FYday',
        'ADMIN',
        '081234567890',
        'GMS Library',
        'ACTIVE'
      WHERE NOT EXISTS (
        SELECT 1
        FROM librarian
        WHERE librarian_id = 1
      )
      SQL,
      <<<SQL
      SELECT setval(
        pg_get_serial_sequence('librarian', 'librarian_id'),
        GREATEST((SELECT COALESCE(MAX(librarian_id), 1) FROM librarian), 1)
      )
      SQL,
    ],
  ];

  $check = $pdo->prepare("SELECT 1 FROM schema_migrations WHERE version = ?");
  $insert = $pdo->prepare("INSERT INTO schema_migrations (version) VALUES (?)");

  foreach ($migrations as $version => $statements) {
    $check->execute([$version]);

    if ($check->fetchColumn()) {
      continue;
    }

    try {
      $pdo->beginTransaction();

      foreach ($statements as $statement) {
        $pdo->exec($statement);
      }

      $insert->execute([$version]);
      $pdo->commit();
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }

      die("Database migration failed ({$version}): " . $e->getMessage());
    }
  }
}

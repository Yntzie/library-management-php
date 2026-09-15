<?php

$basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
$autoloadPath = $basePath . "/vendor/autoload.php";

if (file_exists($autoloadPath)) {
  require_once $autoloadPath;
}

if (class_exists(Dotenv\Dotenv::class) && file_exists($basePath . "/.env")) {
  Dotenv\Dotenv::createImmutable($basePath)->safeLoad();
}

class PgResult
{
  public int $num_rows;
  private array $rows;
  private int $position = 0;

  public function __construct(array $rows = [])
  {
    $this->rows = $rows;
    $this->num_rows = count($rows);
  }

  public function fetch_assoc(): ?array
  {
    if ($this->position >= $this->num_rows) {
      return null;
    }

    return $this->rows[$this->position++];
  }

  public function fetch_all(): array
  {
    return $this->rows;
  }
}

class PgStatement
{
  public int $num_rows = 0;
  public string $error = '';
  private PgConnection $conn;
  private string $sql;
  private array $params = [];
  private PgResult $result;

  public function __construct(PgConnection $conn, string $sql)
  {
    $this->conn = $conn;
    $this->sql = $sql;
    $this->result = new PgResult();
  }

  public function bind_param(string $types, &...$vars): bool
  {
    $this->params = [];

    foreach ($vars as $index => &$value) {
      $type = $types[$index] ?? 's';
      $this->params[] = match ($type) {
        'i' => (int) $value,
        'd' => (float) $value,
        default => $value,
      };
    }

    return true;
  }

  public function execute(): bool
  {
    try {
      $statement = $this->conn->pdo()->prepare($this->conn->prepareInsertReturning($this->sql));
      $statement->execute($this->params);

      $rows = $statement->columnCount() > 0 ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
      $this->result = new PgResult($rows);
      $this->num_rows = $this->result->num_rows;
      $this->conn->captureInsertId($this->sql, $rows);

      return true;
    } catch (PDOException $e) {
      $this->error = $e->getMessage();
      $this->conn->error = $this->error;
      throw $e;
    }
  }

  public function get_result(): PgResult
  {
    return $this->result;
  }

  public function store_result(): bool
  {
    $this->num_rows = $this->result->num_rows;
    return true;
  }

  public function close(): bool
  {
    return true;
  }
}

class PgConnection
{
  public string $error = '';
  public ?string $connect_error = null;
  public int|string $insert_id = 0;
  private PDO $pdo;
  private array $primaryKeys = [
    'user' => 'user_id',
    'librarian' => 'librarian_id',
    'book' => 'book_id',
    'borrow' => 'borrow_id',
    'return_book' => 'return_id',
    'fine' => 'fine_id',
  ];

  public function __construct(string $host, string $user, string $pass, string $db, int $port = 5432, string $sslmode = 'require')
  {
    $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode={$sslmode}";

    try {
      $this->pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
    } catch (PDOException $e) {
      $this->connect_error = $e->getMessage();
      $this->error = $e->getMessage();
      throw $e;
    }
  }

  public function pdo(): PDO
  {
    return $this->pdo;
  }

  public function prepare(string $sql): PgStatement
  {
    return new PgStatement($this, $this->normalizeSql($sql));
  }

  public function query(string $sql): PgResult
  {
    try {
      $sql = $this->prepareInsertReturning($this->normalizeSql($sql));
      $statement = $this->pdo->query($sql);
      $rows = ($statement && $statement->columnCount() > 0) ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
      $this->captureInsertId($sql, $rows);

      return new PgResult($rows);
    } catch (PDOException $e) {
      $this->error = $e->getMessage();
      throw $e;
    }
  }

  public function prepareInsertReturning(string $sql): string
  {
    if (stripos($sql, ' returning ') !== false) {
      return $sql;
    }

    if (!preg_match('/^\s*INSERT\s+INTO\s+"?([a-z_]+)"?/i', $sql, $matches)) {
      return $sql;
    }

    $table = strtolower($matches[1]);
    $primaryKey = $this->primaryKeys[$table] ?? null;

    return $primaryKey ? rtrim($sql, " \t\n\r\0\x0B;") . ' RETURNING ' . $this->quoteIdentifier($primaryKey) : $sql;
  }

  public function captureInsertId(string $sql, array $rows): void
  {
    if (!preg_match('/^\s*INSERT\s+INTO\s+"?([a-z_]+)"?/i', $sql, $matches)) {
      return;
    }

    $table = strtolower($matches[1]);
    $primaryKey = $this->primaryKeys[$table] ?? null;

    if ($primaryKey && isset($rows[0][$primaryKey])) {
      $this->insert_id = $rows[0][$primaryKey];
    }
  }

  public function set_charset(string $charset): bool
  {
    return true;
  }

  public function close(): bool
  {
    return true;
  }

  private function normalizeSql(string $sql): string
  {
    foreach (array_keys($this->primaryKeys) as $table) {
      $quoted = $this->quoteIdentifier($table);
      $sql = preg_replace('/\b(FROM|JOIN|INTO|UPDATE)\s+' . preg_quote($table, '/') . '\b/i', '$1 ' . $quoted, $sql);
      $sql = preg_replace('/\b(DELETE\s+FROM)\s+' . preg_quote($table, '/') . '\b/i', '$1 ' . $quoted, $sql);
    }

    return $sql;
  }

  private function quoteIdentifier(string $identifier): string
  {
    return '"' . str_replace('"', '""', $identifier) . '"';
  }
}

$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: null;
$user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: null;
$pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: "";
$db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: null;
$port = (int) ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 5432);
$sslmode = $_ENV['DB_SSLMODE'] ?? getenv('DB_SSLMODE') ?: 'require';

if (!$host || !$user || !$db) {
  die("PostgreSQL environment variables are not configured.");
}

if (!in_array('pgsql', PDO::getAvailableDrivers(), true)) {
  die("PostgreSQL PDO driver is not enabled. Enable the pdo_pgsql extension in php.ini.");
}

try {
  $conn = new PgConnection($host, $user, $pass, $db, $port, $sslmode);
} catch (PDOException $e) {
  die("PostgreSQL connection failed: " . $e->getMessage());
}

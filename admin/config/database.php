<?php
// ============================================================
// PalCus Admin — Conexión PDO (Singleton)
// ============================================================

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En producción no exponer detalles
            $msg = APP_ENV === 'development'
                ? 'Error de conexión: ' . $e->getMessage()
                : 'Error de conexión a la base de datos.';
            die(json_encode(['error' => $msg]));
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // Helper: ejecutar query y retornar todos los resultados
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Helper: ejecutar query y retornar una fila
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Helper: ejecutar INSERT/UPDATE/DELETE
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->rowCount();
    }

    // Obtener último ID insertado
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    // Transacciones
    public function beginTransaction(): void  { $this->pdo->beginTransaction(); }
    public function commit(): void            { $this->pdo->commit(); }
    public function rollBack(): void          { $this->pdo->rollBack(); }
}

// Acceso global rápido
function db(): Database {
    return Database::getInstance();
}

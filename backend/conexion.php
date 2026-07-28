<?php
declare(strict_types=1);

// ---- Datos de conexión (local por ahora, MySQL) ----
$host     = "127.0.0.1";
$user     = "root";
$password = "";
$database = "bibliotecav2";
$charset  = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$database;charset=$charset";

// Configuración de PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'estado'  => 'error',
        'mensaje' => 'Error al conectar a la base de datos: ' . $e->getMessage()
    ]);
    exit();
}
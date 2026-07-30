<?php
declare(strict_types=1);

// ---- Datos de conexión (PostgreSQL) ----
// En Render, la base de datos se conecta a través de la variable de entorno
// DATABASE_URL (Render la genera sola al crear el Postgres). En local, si esa
// variable no existe, usamos los valores de abajo (ajústalos a tu instalación).
$urlConexion = getenv('DATABASE_URL');

if ($urlConexion) {
    $partes = parse_url($urlConexion);
    $host     = $partes['host'];
    $port     = $partes['port'] ?? '5432';
    $user     = $partes['user'];
    $password = $partes['pass'];
    $database = ltrim($partes['path'], '/');
} else {
    $host     = "127.0.0.1";
    $port     = "5432";
    $user     = "postgres";
    $password = "";
    $database = "biblioteca";
}

$dsn = "pgsql:host=$host;port=$port;dbname=$database";

// Render exige SSL para conectarse a su Postgres desde fuera de su red interna
if ($urlConexion) {
    $dsn .= ";sslmode=require";
}

// Configuración de PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
    // Forzar la codificación a UTF-8 en la conexión
    $pdo->exec("SET NAMES 'UTF8'");
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'estado'  => 'error',
        'mensaje' => 'Error al conectar a la base de datos: ' . $e->getMessage()
    ]);
    exit();
}
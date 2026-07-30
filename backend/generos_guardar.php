<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';
require_once 'includes/ids.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../libros.php');
    exit();
}

$geNombre = trim($_POST['ge_nombre'] ?? '');

if ($geNombre === '') {
    header('Location: ../libros.php?error=generovacio');
    exit();
}

try {
    // Validar que el género no exista ya (ignorando mayúsculas/minúsculas)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM genero WHERE LOWER(ge_nombre) = LOWER(?)");
    $stmt->execute([$geNombre]);

    if ((int)$stmt->fetchColumn() > 0) {
        header('Location: ../libros.php?error=generoduplicado');
        exit();
    }

    // Insertar el nuevo género con id manual (GE-0000)
    $idGenero = generarSiguienteId($pdo, 'genero', 'id_genero', 'GE');
    $stmt = $pdo->prepare("INSERT INTO genero (id_genero, ge_nombre) VALUES (?, ?)");
    $stmt->execute([$idGenero, $geNombre]);

    header('Location: ../libros.php?ok=generocreado');
    exit();
} catch (PDOException $e) {
    die("Error al guardar el género: " . $e->getMessage());
}
<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_ejemplar'])) {
    header('Location: ../ejemplares.php');
    exit();
}

$idEjemplar = trim($_POST['id_ejemplar']);

try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM prestamo WHERE id_ejemplar = ? AND pr_f_dev IS NULL"
    );
    $stmt->execute([$idEjemplar]);

    if ((int)$stmt->fetchColumn() > 0) {
        header('Location: ../ejemplares.php?error=prestamoactivo');
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM ejemplar WHERE id_ejemplar = ?");
    $stmt->execute([$idEjemplar]);
    header('Location: ../ejemplares.php');
    exit();
} catch (PDOException $e) {
    die("Error al eliminar el ejemplar: " . $e->getMessage());
}
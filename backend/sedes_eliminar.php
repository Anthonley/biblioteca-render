<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_sede'])) {
    header('Location: ../sedes.php');
    exit();
}

$idSede = trim($_POST['id_sede']);

try {
    // Una sede con ejemplares no se puede borrar: el CASCADE se llevaría
    // por delante esos ejemplares (y sus préstamos) sin avisar.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ejemplar WHERE id_sede = ?");
    $stmt->execute([$idSede]);

    if ((int)$stmt->fetchColumn() > 0) {
        header('Location: ../sedes.php?error=tieneejemplares');
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM sede_biblioteca WHERE id_sede = ?");
    $stmt->execute([$idSede]);
    header('Location: ../sedes.php');
    exit();
} catch (PDOException $e) {
    die("Error al eliminar la sede: " . $e->getMessage());
}
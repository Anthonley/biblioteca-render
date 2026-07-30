<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_socio'])) {
    header('Location: ../socios.php');
    exit();
}

$idSocio = trim($_POST['id_socio']);

try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM prestamo WHERE id_socio = ? AND pr_f_dev IS NULL"
    );
    $stmt->execute([$idSocio]);

    if ((int)$stmt->fetchColumn() > 0) {
        header('Location: ../socios.php?error=prestamosactivos');
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM socio WHERE id_socio = ?");
    $stmt->execute([$idSocio]);
    header('Location: ../socios.php');
    exit();
} catch (PDOException $e) {
    die("Error al eliminar el socio: " . $e->getMessage());
}
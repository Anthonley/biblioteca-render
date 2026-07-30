<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_prestamo'])) {
    header('Location: ../prestamos.php');
    exit();
}

$idPrestamo = trim($_POST['id_prestamo']);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id_ejemplar, pr_f_dev FROM prestamo WHERE id_prestamo = ? FOR UPDATE");
    $stmt->execute([$idPrestamo]);
    $prestamo = $stmt->fetch();

    if (!$prestamo || $prestamo['pr_f_dev'] !== null) {
        // No existe o ya estaba devuelto: no hay nada que hacer
        $pdo->rollBack();
        header('Location: ../prestamos.php');
        exit();
    }

    $pdo->prepare(
        "UPDATE prestamo SET pr_f_dev = CURRENT_DATE WHERE id_prestamo = ?"
    )->execute([$idPrestamo]);

    $pdo->prepare(
        "UPDATE ejemplar SET ej_estado = 'Disponible' WHERE id_ejemplar = ?"
    )->execute([$prestamo['id_ejemplar']]);

    $pdo->commit();
    header('Location: ../prestamos.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error al registrar la devolución: " . $e->getMessage());
}
<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_libro'])) {
    header('Location: ../libros.php');
    exit();
}

$idLibro = trim($_POST['id_libro']);

try {
    // No permitir borrar si alguno de sus ejemplares tiene un préstamo activo (sin devolver)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM prestamo p
         INNER JOIN ejemplar e ON e.id_ejemplar = p.id_ejemplar
         WHERE e.id_libro = ? AND p.pr_f_dev IS NULL"
    );
    $stmt->execute([$idLibro]);

    if ((int)$stmt->fetchColumn() > 0) {
        header('Location: ../libros.php?error=prestamosactivos');
        exit();
    }

    // ON DELETE CASCADE en ejemplar/tag se encarga del resto
    $stmt = $pdo->prepare("DELETE FROM libro WHERE id_libro = ?");
    $stmt->execute([$idLibro]);
    header('Location: ../libros.php');
    exit();
} catch (PDOException $e) {
    die("Error al eliminar el libro: " . $e->getMessage());
}
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

$idPrestamo = (int)$_POST['id_prestamo'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id_ejemplar, pr_f_dev FROM prestamo WHERE id_prestamo = ? FOR UPDATE");
    $stmt->execute([$idPrestamo]);
    $prestamo = $stmt->fetch();

    if ($prestamo) {
        // Si el préstamo seguía activo, el ejemplar vuelve a estar Disponible
        if ($prestamo['pr_f_dev'] === null) {
            $pdo->prepare(
                "UPDATE ejemplar SET ej_estado = 'Disponible' WHERE id_ejemplar = ?"
            )->execute([$prestamo['id_ejemplar']]);
        }
        $pdo->prepare("DELETE FROM prestamo WHERE id_prestamo = ?")->execute([$idPrestamo]);
    }

    $pdo->commit();
    header('Location: ../prestamos.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error al eliminar el préstamo: " . $e->getMessage());
}
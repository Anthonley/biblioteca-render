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
    header('Location: ../prestamos.php');
    exit();
}

$idSocio    = trim($_POST['id_socio'] ?? '');
$idEjemplar = trim($_POST['id_ejemplar'] ?? '');

if ($idSocio === '' || $idEjemplar === '') {
    header('Location: ../prestamos.php?error=noselecciono');
    exit();
}

try {
    $pdo->beginTransaction();

    // Verificamos que siga Disponible (por si otra persona lo tomó mientras
    // este usuario tenía el modal abierto) y bloqueamos la fila
    $stmt = $pdo->prepare("SELECT ej_estado FROM ejemplar WHERE id_ejemplar = ? FOR UPDATE");
    $stmt->execute([$idEjemplar]);
    $ejemplar = $stmt->fetch();

    if (!$ejemplar || $ejemplar['ej_estado'] !== 'Disponible') {
        $pdo->rollBack();
        header('Location: ../prestamos.php?error=nodisponible');
        exit();
    }

    $idPrestamo = generarSiguienteId($pdo, 'prestamo', 'id_prestamo', 'PR');
    $pdo->prepare(
        "INSERT INTO prestamo (id_prestamo, id_ejemplar, id_socio) VALUES (?, ?, ?)"
    )->execute([$idPrestamo, $idEjemplar, $idSocio]);

    $pdo->prepare(
        "UPDATE ejemplar SET ej_estado = 'Prestado' WHERE id_ejemplar = ?"
    )->execute([$idEjemplar]);

    $pdo->commit();
    header('Location: ../prestamos.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error al registrar el préstamo: " . $e->getMessage());
}
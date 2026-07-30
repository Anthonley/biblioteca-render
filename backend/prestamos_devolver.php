<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

// Tarifas de multa. Ecuador usa USD, así que se maneja directo en dólares.
const MULTA_POR_DIA_ATRASO = 0.50;
const MULTA_DANADO         = 5.00;
const MULTA_PERDIDO        = 15.00;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_prestamo'])) {
    header('Location: ../prestamos.php');
    exit();
}

$idPrestamo = trim($_POST['id_prestamo']);
$estado     = trim($_POST['pr_estado_devolucion'] ?? '');

$estadosValidos = ['Bueno', 'Dañado', 'Perdido'];
if (!in_array($estado, $estadosValidos, true)) {
    header('Location: ../prestamos.php?error=estadoinvalido');
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id_ejemplar, pr_f_dev_esperada, pr_f_dev_real FROM prestamo WHERE id_prestamo = ? FOR UPDATE");
    $stmt->execute([$idPrestamo]);
    $prestamo = $stmt->fetch();

    if (!$prestamo || $prestamo['pr_f_dev_real'] !== null) {
        // No existe o ya estaba devuelto: no hay nada que hacer
        $pdo->rollBack();
        header('Location: ../prestamos.php');
        exit();
    }

    // Días de atraso a la fecha de hoy (0 si todavía no se pasa de la fecha esperada)
    $hoy      = new DateTime('today');
    $esperada = new DateTime($prestamo['pr_f_dev_esperada']);
    $diasAtraso = $hoy > $esperada ? $hoy->diff($esperada)->days : 0;

    $multa = $diasAtraso * MULTA_POR_DIA_ATRASO;
    if ($estado === 'Dañado') {
        $multa += MULTA_DANADO;
    } elseif ($estado === 'Perdido') {
        $multa += MULTA_PERDIDO;
    }

    $pdo->prepare(
        "UPDATE prestamo
         SET pr_f_dev_real = CURRENT_DATE, pr_estado_devolucion = ?, pr_multa = ?
         WHERE id_prestamo = ?"
    )->execute([$estado, $multa, $idPrestamo]);

    // El estado del ejemplar depende de cómo se devolvió:
    // Bueno -> vuelve a estar disponible para prestarse
    // Dañado -> pasa a reparación
    // Perdido -> se marca como extraviado
    $nuevoEstadoEjemplar = match ($estado) {
        'Dañado'  => 'En Reparación',
        'Perdido' => 'Extraviado',
        default   => 'Disponible',
    };

    $pdo->prepare(
        "UPDATE ejemplar SET ej_estado = ? WHERE id_ejemplar = ?"
    )->execute([$nuevoEstadoEjemplar, $prestamo['id_ejemplar']]);

    $pdo->commit();
    header('Location: ../prestamos.php?ok=devuelto&multa=' . urlencode((string) $multa));
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error al registrar la devolución: " . $e->getMessage());
}
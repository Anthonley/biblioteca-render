<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../socios.php');
    exit();
}

$idSocio  = trim($_POST['id_socio'] ?? '');
$cedula   = trim($_POST['so_cedula'] ?? '');
$nombre   = trim($_POST['so_nombre'] ?? '');
$apellido = trim($_POST['so_apellido'] ?? '');
$telefono = trim($_POST['so_telefono'] ?? '');

if ($cedula === '' || $nombre === '' || $apellido === '') {
    header('Location: ../socios.php?error=camposobligatorios');
    exit();
}

try {
    // La cédula no puede repetirse (la BD ya tiene UNIQUE, pero validamos
    // antes para dar un mensaje claro en vez de un error genérico de PDO)
    if ($idSocio === '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM socio WHERE so_cedula = ?");
        $stmt->execute([$cedula]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM socio WHERE so_cedula = ? AND id_socio <> ?");
        $stmt->execute([$cedula, (int)$idSocio]);
    }

    if ((int)$stmt->fetchColumn() > 0) {
        header('Location: ../socios.php?error=ceduladuplicada');
        exit();
    }

    if ($idSocio === '') {
        $stmt = $pdo->prepare(
            "INSERT INTO socio (so_cedula, so_nombre, so_apellido, so_telefono) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$cedula, $nombre, $apellido, $telefono !== '' ? $telefono : null]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE socio SET so_cedula = ?, so_nombre = ?, so_apellido = ?, so_telefono = ? WHERE id_socio = ?"
        );
        $stmt->execute([$cedula, $nombre, $apellido, $telefono !== '' ? $telefono : null, (int)$idSocio]);
    }

    header('Location: ../socios.php');
    exit();
} catch (PDOException $e) {
    header('Location: ../socios.php?error=ceduladuplicada');
    exit();
}
<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../sedes.php');
    exit();
}

$idSede    = trim($_POST['id_sede'] ?? '');
$nombre    = trim($_POST['se_nombre'] ?? '');
$direccion = trim($_POST['se_direccion'] ?? '');

if ($nombre === '' || $direccion === '') {
    header('Location: ../sedes.php?error=camposobligatorios');
    exit();
}

try {
    if ($idSede === '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sede_biblioteca WHERE LOWER(se_nombre) = LOWER(?)");
        $stmt->execute([$nombre]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sede_biblioteca WHERE LOWER(se_nombre) = LOWER(?) AND id_sede <> ?");
        $stmt->execute([$nombre, (int)$idSede]);
    }

    if ((int)$stmt->fetchColumn() > 0) {
        header('Location: ../sedes.php?error=nombreduplicado');
        exit();
    }

    if ($idSede === '') {
        $stmt = $pdo->prepare(
            "INSERT INTO sede_biblioteca (se_nombre, se_direccion) VALUES (?, ?)"
        );
        $stmt->execute([$nombre, $direccion]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE sede_biblioteca SET se_nombre = ?, se_direccion = ? WHERE id_sede = ?"
        );
        $stmt->execute([$nombre, $direccion, (int)$idSede]);
    }

    header('Location: ../sedes.php');
    exit();
} catch (PDOException $e) {
    die("Error al guardar la sede: " . $e->getMessage());
}
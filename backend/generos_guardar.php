<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ejemplares.php');
    exit();
}

// "Prestado" ya no es un estado asignable a mano: lo controla el flujo de Préstamos
$estadosValidos = ['Disponible', 'En Reparación', 'Extraviado'];

$idEjemplar = trim($_POST['id_ejemplar'] ?? '');
$idLibro    = (int)($_POST['id_libro'] ?? 0);
$idSede     = (int)($_POST['id_sede'] ?? 0);
$estado     = trim($_POST['ej_estado'] ?? '');
$cantidad   = max(1, min(50, (int)($_POST['cantidad'] ?? 1)));

if (!$idLibro || !$idSede || !in_array($estado, $estadosValidos, true)) {
    header('Location: ../ejemplares.php?error=camposobligatorios');
    exit();
}

try {
    if ($idEjemplar === '') {
        // ---- Crear una o varias copias idénticas ----
        $stmt = $pdo->prepare(
            "INSERT INTO ejemplar (id_libro, id_sede, ej_estado) VALUES (?, ?, ?)"
        );
        for ($i = 0; $i < $cantidad; $i++) {
            $stmt->execute([$idLibro, $idSede, $estado]);
        }
    } else {
        // ---- Actualizar un ejemplar puntual ----
        $stmt = $pdo->prepare(
            "UPDATE ejemplar SET id_libro = ?, id_sede = ?, ej_estado = ? WHERE id_ejemplar = ?"
        );
        $stmt->execute([$idLibro, $idSede, $estado, (int)$idEjemplar]);
    }

    header('Location: ../ejemplares.php');
    exit();
} catch (PDOException $e) {
    die("Error al guardar el ejemplar: " . $e->getMessage());
}
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
    header('Location: ../ejemplares.php');
    exit();
}

$estadosValidos = ['Disponible', 'Prestado', 'En Reparación', 'Extraviado'];

$idEjemplar = trim($_POST['id_ejemplar'] ?? '');
$idLibro    = trim($_POST['id_libro'] ?? '');
$idSede     = trim($_POST['id_sede'] ?? '');
$estado     = trim($_POST['ej_estado'] ?? '');
$cantidad   = max(1, min(50, (int)($_POST['cantidad'] ?? 1)));

if ($idLibro === '' || $idSede === '' || !in_array($estado, $estadosValidos, true)) {
    header('Location: ../ejemplares.php?error=camposobligatorios');
    exit();
}

try {
    if ($idEjemplar === '') {
        // ---- Crear una o varias copias idénticas, cada una con su propio id EJ-0000 ----
        $stmt = $pdo->prepare(
            "INSERT INTO ejemplar (id_ejemplar, id_libro, id_sede, ej_estado) VALUES (?, ?, ?, ?)"
        );
        for ($i = 0; $i < $cantidad; $i++) {
            $idNuevoEjemplar = generarSiguienteId($pdo, 'ejemplar', 'id_ejemplar', 'EJ');
            $stmt->execute([$idNuevoEjemplar, $idLibro, $idSede, $estado]);
        }
    } else {
        // ---- Actualizar un ejemplar puntual ----
        $stmt = $pdo->prepare(
            "UPDATE ejemplar SET id_libro = ?, id_sede = ?, ej_estado = ? WHERE id_ejemplar = ?"
        );
        $stmt->execute([$idLibro, $idSede, $estado, $idEjemplar]);
    }

    header('Location: ../ejemplares.php');
    exit();
} catch (PDOException $e) {
    die("Error al guardar el ejemplar: " . $e->getMessage());
}
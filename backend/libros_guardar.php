<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: ../index.php');
    exit();
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../libros.php');
    exit();
}

$idLibro   = trim($_POST['id_libro'] ?? '');
$titulo    = trim($_POST['li_titulo'] ?? '');
$autor     = trim($_POST['li_autor'] ?? '');
$editorial = trim($_POST['li_editorial'] ?? '');
$generos   = $_POST['generos'] ?? []; // array de id_genero

if ($titulo === '' || $autor === '') {
    header('Location: ../libros.php?error=camposobligatorios');
    exit();
}

try {
    $pdo->beginTransaction();

    if ($idLibro === '') {
        // ---- Crear libro nuevo ----
        $stmt = $pdo->prepare(
            "INSERT INTO libro (li_titulo, li_autor, li_editorial) VALUES (?, ?, ?)"
        );
        $stmt->execute([$titulo, $autor, $editorial !== '' ? $editorial : null]);
        $idLibro = (int)$pdo->lastInsertId();
    } else {
        // ---- Actualizar libro existente ----
        $idLibro = (int)$idLibro;
        $stmt = $pdo->prepare(
            "UPDATE libro SET li_titulo = ?, li_autor = ?, li_editorial = ? WHERE id_libro = ?"
        );
        $stmt->execute([$titulo, $autor, $editorial !== '' ? $editorial : null, $idLibro]);

        // Reemplazamos los géneros: borramos los tag actuales y volvemos a insertar
        $pdo->prepare("DELETE FROM tag WHERE id_libro = ?")->execute([$idLibro]);
    }

    if (!empty($generos)) {
        $stmtTag = $pdo->prepare("INSERT INTO tag (id_libro, id_genero) VALUES (?, ?)");
        foreach ($generos as $idGenero) {
            $stmtTag->execute([$idLibro, (int)$idGenero]);
        }
    }

    $pdo->commit();
    header('Location: ../libros.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error al guardar el libro: " . $e->getMessage());
}
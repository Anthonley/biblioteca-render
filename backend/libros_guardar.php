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
    header('Location: ../libros.php');
    exit();
}

$idLibro         = trim($_POST['id_libro'] ?? '');
$idLibroOriginal = trim($_POST['id_libro_original'] ?? '');
$titulo          = trim($_POST['li_titulo'] ?? '');
$autor           = trim($_POST['li_autor'] ?? '');
$editorial       = trim($_POST['li_editorial'] ?? '');
$isbn            = trim($_POST['li_isbn'] ?? '');
$generos         = $_POST['generos'] ?? []; // array de id_genero

$esEdicion = $idLibroOriginal !== '';

// ---- ID: acepta "LI-0007", "li-7" o solo "7"; siempre se guarda como LI-0000 ----
$idNormalizado = strtoupper($idLibro);
if (str_starts_with($idNormalizado, 'LI-')) {
    $idNormalizado = substr($idNormalizado, 3);
}
$idNormalizado = preg_replace('/\D/', '', $idNormalizado);

if ($idNormalizado === '' || strlen($idNormalizado) > 4) {
    header('Location: ../libros.php?error=idinvalido');
    exit();
}
$idLibro = 'LI-' . str_pad($idNormalizado, 4, '0', STR_PAD_LEFT);

// ---- Título: obligatorio, puede ser cualquier texto ----
if ($titulo === '') {
    header('Location: ../libros.php?error=camposobligatorios');
    exit();
}

// ---- Autor: si viene algo, solo letras y mínimo 2; si viene vacío, Anónimo ----
$soloLetras = '/^[A-Za-zÀ-ÖØ-öø-ÿñÑ\s]+$/u';
if ($autor === '') {
    $autor = 'Anónimo';
} else {
    $letras = preg_match_all('/[A-Za-zÀ-ÖØ-öø-ÿñÑ]/u', $autor);
    if (!preg_match($soloLetras, $autor) || $letras < 2) {
        header('Location: ../libros.php?error=autorinvalido');
        exit();
    }
}

// ---- Editorial: si viene algo, misma regla que autor; si viene vacío, se queda en null ----
if ($editorial !== '') {
    $letras = preg_match_all('/[A-Za-zÀ-ÖØ-öø-ÿñÑ]/u', $editorial);
    if (!preg_match($soloLetras, $editorial) || $letras < 2) {
        header('Location: ../libros.php?error=editorialinvalida');
        exit();
    }
}

// ---- ISBN: opcional; si viene, debe quedar en 10 o 13 dígitos (se ignoran guiones/espacios) ----
// ---- ISBN: opcional; si viene, debe tener 10 o 13 caracteres (números o 'X') ----
if ($isbn !== '') {
    // Limpiamos cualquier guion o espacio que el usuario haya puesto por costumbre
    $isbnLimpio = preg_replace('/[\s-]/', '', $isbn);
    
    $valido10 = preg_match('/^\d{9}[\dXx]$/', $isbnLimpio);
    $valido13 = preg_match('/^\d{13}$/', $isbnLimpio);
    
    if (!$valido10 && !$valido13) {
        header('Location: ../libros.php?error=isbninvalido');
        exit();
    }
    
    // Reemplazamos la variable original por la limpia y en mayúscula (por la 'X')
    // Esta es la variable que se insertará en la base de datos
    $isbn = strtoupper($isbnLimpio);
}

try {
    $pdo->beginTransaction();

    if (!$esEdicion) {
        // ---- Crear libro nuevo ----
        // Verificar que el ID no exista ya
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM libro WHERE id_libro = ?");
        $stmt->execute([$idLibro]);
        if ((int)$stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            header('Location: ../libros.php?error=idduplicado');
            exit();
        }

        $stmt = $pdo->prepare(
            "INSERT INTO libro (id_libro, li_titulo, li_autor, li_editorial, li_isbn) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $idLibro,
            $titulo,
            $autor,
            $editorial !== '' ? $editorial : null,
            $isbn !== '' ? $isbn : null,
        ]);
    } else {
        // ---- Actualizar libro existente (el ID no cambia) ----
        $stmt = $pdo->prepare(
            "UPDATE libro SET li_titulo = ?, li_autor = ?, li_editorial = ?, li_isbn = ? WHERE id_libro = ?"
        );
        $stmt->execute([
            $titulo,
            $autor,
            $editorial !== '' ? $editorial : null,
            $isbn !== '' ? $isbn : null,
            $idLibroOriginal,
        ]);
        $idLibro = $idLibroOriginal;

        // Reemplazamos los géneros: borramos los tag actuales y volvemos a insertar
        $pdo->prepare("DELETE FROM tag WHERE id_libro = ?")->execute([$idLibro]);
    }

    if (!empty($generos)) {
        $stmtTag = $pdo->prepare("INSERT INTO tag (id_tag, id_libro, id_genero) VALUES (?, ?, ?)");
        foreach ($generos as $idGenero) {
            $idTag = generarSiguienteId($pdo, 'tag', 'id_tag', 'TA');
            $stmtTag->execute([$idTag, $idLibro, $idGenero]);
        }
    }

    $pdo->commit();
    header('Location: ../libros.php');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error al guardar el libro: " . $e->getMessage());
}
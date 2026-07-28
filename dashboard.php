<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel principal · Biblioteca Municipal</title>
    <link rel="stylesheet" href="frontend/css/estilo.css">
</head>
<body>
    <?php include 'backend/includes/navbar.php'; ?>

    <main class="lib-contenido">
        <div class="lib-bienvenida">
            <h2>Bienvenido, <?= htmlspecialchars(ucfirst($usuario['nombre'])) ?></h2>
            <p>Seleccione una sección del menú superior para gestionar libros, ejemplares, socios, préstamos o sedes.</p>
        </div>
    </main>
</body>
</html>
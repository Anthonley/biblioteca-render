<?php
declare(strict_types=1);
session_start();

if (isset($_SESSION['usuario_activo'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Municipal · Acceso</title>
    <link rel="stylesheet" href="frontend/css/estilo.css">
</head>
<body>
    <div class="lib-login-wrap">
        <div class="lib-login-card">
            <h2>📚 Biblioteca Municipal</h2>
            <p>Ingrese sus credenciales para administrar el sistema.</p>

            <?php if (isset($_GET['error'])): ?>
                <div class="lib-alerta">Usuario o contraseña incorrectos. Inténtelo de nuevo.</div>
            <?php endif; ?>

            <form action="backend/procesar_login.php" method="POST">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required autocomplete="off">

                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required>

                <button type="submit" class="lib-btn-primario">Ingresar</button>
            </form>
        </div>
    </div>
</body>
</html>
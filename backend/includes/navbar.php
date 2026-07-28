<header class="lib-navbar">
    <div class="lib-navbar__brand">
        <span class="lib-navbar__icono">📚</span>
        <div>
            <h1>Biblioteca Municipal</h1>
            <small>Sistema de gestión de préstamos</small>
        </div>
    </div>

    <nav class="lib-navbar__links">
        <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'activo' : '' ?>">Inicio</a>
        <a href="libros.php" class="<?= basename($_SERVER['PHP_SELF']) === 'libros.php' ? 'activo' : '' ?>">Libros</a>
        <a href="ejemplares.php" class="<?= basename($_SERVER['PHP_SELF']) === 'ejemplares.php' ? 'activo' : '' ?>">Ejemplares</a>
        <a href="socios.php" class="<?= basename($_SERVER['PHP_SELF']) === 'socios.php' ? 'activo' : '' ?>">Socios</a>
        <a href="prestamos.php" class="<?= basename($_SERVER['PHP_SELF']) === 'prestamos.php' ? 'activo' : '' ?>">Préstamos</a>
        <a href="sedes.php" class="<?= basename($_SERVER['PHP_SELF']) === 'sedes.php' ? 'activo' : '' ?>">Sedes</a>
    </nav>

    <div class="lib-navbar__usuario">
        <span>👤 <?= htmlspecialchars(strtoupper($usuario['nombre'])) ?> · <?= htmlspecialchars(ucfirst($usuario['rol'])) ?></span>
        <a href="backend/logout.php" class="lib-btn-salir">Cerrar sesión</a>
    </div>
</header>
<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];

require_once 'backend/conexion.php';

// ---- Totales generales ----
$totalLibros      = (int) $pdo->query("SELECT COUNT(*) FROM libro")->fetchColumn();
$totalEjemplares  = (int) $pdo->query("SELECT COUNT(*) FROM ejemplar")->fetchColumn();
$totalSocios      = (int) $pdo->query("SELECT COUNT(*) FROM socio")->fetchColumn();
$totalSedes       = (int) $pdo->query("SELECT COUNT(*) FROM sede_biblioteca")->fetchColumn();
$totalGeneros     = (int) $pdo->query("SELECT COUNT(*) FROM genero")->fetchColumn();
$prestamosActivos = (int) $pdo->query("SELECT COUNT(*) FROM prestamo WHERE pr_f_dev_real IS NULL")->fetchColumn();
$prestamosTotal   = (int) $pdo->query("SELECT COUNT(*) FROM prestamo")->fetchColumn();
$prestamosDevueltos = $prestamosTotal - $prestamosActivos;

// ---- Ejemplares agrupados por estado ----
$ejemplaresPorEstado = $pdo->query(
    "SELECT ej_estado, COUNT(*) AS total
     FROM ejemplar
     GROUP BY ej_estado
     ORDER BY total DESC"
)->fetchAll();

// ---- Top 5 libros más prestados ----
$librosMasPrestados = $pdo->query(
    "SELECT l.li_titulo, COUNT(*) AS veces
     FROM prestamo p
     JOIN ejemplar e ON e.id_ejemplar = p.id_ejemplar
     JOIN libro l ON l.id_libro = e.id_libro
     GROUP BY l.li_titulo
     ORDER BY veces DESC
     LIMIT 5"
)->fetchAll();

// ---- Sedes con más ejemplares ----
$sedesConMasEjemplares = $pdo->query(
    "SELECT s.se_nombre, COUNT(e.id_ejemplar) AS total
     FROM sede_biblioteca s
     LEFT JOIN ejemplar e ON e.id_sede = s.id_sede
     GROUP BY s.se_nombre
     ORDER BY total DESC
     LIMIT 5"
)->fetchAll();

// Helper para las clases de badge de estado (reutiliza .lib-estado--*)
function claseEstado(string $estado): string {
    $slug = strtolower(str_replace(' ', '-', $estado));
    return 'lib-estado--' . $slug;
}
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
            <p>Este es un resumen general del estado actual de la biblioteca.</p>
        </div>

        <!-- ================= TARJETAS DE ESTADÍSTICAS ================= -->
        <div class="lib-stats-grid">
            <div class="lib-stat-card">
                <span class="lib-stat-card__icono">📖</span>
                <div>
                    <span class="lib-stat-card__valor"><?= $totalLibros ?></span>
                    <span class="lib-stat-card__etiqueta">Libros (<?= $totalGeneros ?> géneros)</span>
                </div>
            </div>
            <div class="lib-stat-card">
                <span class="lib-stat-card__icono">📦</span>
                <div>
                    <span class="lib-stat-card__valor"><?= $totalEjemplares ?></span>
                    <span class="lib-stat-card__etiqueta">Ejemplares</span>
                </div>
            </div>
            <div class="lib-stat-card">
                <span class="lib-stat-card__icono">👥</span>
                <div>
                    <span class="lib-stat-card__valor"><?= $totalSocios ?></span>
                    <span class="lib-stat-card__etiqueta">Socios</span>
                </div>
            </div>
            <div class="lib-stat-card">
                <span class="lib-stat-card__icono">🏛️</span>
                <div>
                    <span class="lib-stat-card__valor"><?= $totalSedes ?></span>
                    <span class="lib-stat-card__etiqueta">Sedes</span>
                </div>
            </div>
            <div class="lib-stat-card">
                <span class="lib-stat-card__icono">🔄</span>
                <div>
                    <span class="lib-stat-card__valor"><?= $prestamosActivos ?></span>
                    <span class="lib-stat-card__etiqueta">Préstamos activos</span>
                </div>
            </div>
            <div class="lib-stat-card">
                <span class="lib-stat-card__icono">✅</span>
                <div>
                    <span class="lib-stat-card__valor"><?= $prestamosDevueltos ?></span>
                    <span class="lib-stat-card__etiqueta">Préstamos devueltos</span>
                </div>
            </div>
        </div>

        <!-- ================= PANELES ================= -->
        <div class="lib-paneles-grid">

            <div class="lib-panel">
                <h3>Ejemplares por estado</h3>
                <?php if (empty($ejemplaresPorEstado)): ?>
                    <p class="lib-vacio">Todavía no hay ejemplares registrados.</p>
                <?php else: ?>
                    <ul class="lib-barra-lista">
                        <?php foreach ($ejemplaresPorEstado as $fila): ?>
                            <?php $pct = $totalEjemplares > 0 ? round(($fila['total'] / $totalEjemplares) * 100) : 0; ?>
                            <li class="lib-barra-item">
                                <div class="lib-barra-item__cabecera">
                                    <span class="lib-estado <?= claseEstado($fila['ej_estado']) ?>"><?= htmlspecialchars($fila['ej_estado']) ?></span>
                                    <span><?= $fila['total'] ?></span>
                                </div>
                                <div class="lib-barra-fondo">
                                    <div class="lib-barra-relleno" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="lib-panel">
                <h3>Libros más prestados</h3>
                <?php if (empty($librosMasPrestados)): ?>
                    <p class="lib-vacio">Todavía no hay préstamos registrados.</p>
                <?php else: ?>
                    <ol class="lib-ranking-lista">
                        <?php foreach ($librosMasPrestados as $fila): ?>
                            <li>
                                <span><?= htmlspecialchars($fila['li_titulo']) ?></span>
                                <span class="lib-ranking-lista__valor"><?= $fila['veces'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>

            <div class="lib-panel">
                <h3>Ejemplares por sede</h3>
                <?php if (empty($sedesConMasEjemplares)): ?>
                    <p class="lib-vacio">Todavía no hay sedes registradas.</p>
                <?php else: ?>
                    <ol class="lib-ranking-lista">
                        <?php foreach ($sedesConMasEjemplares as $fila): ?>
                            <li>
                                <span><?= htmlspecialchars($fila['se_nombre']) ?></span>
                                <span class="lib-ranking-lista__valor"><?= $fila['total'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>

        </div>
    </main>
</body>
</html>
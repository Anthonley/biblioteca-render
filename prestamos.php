<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];

require_once 'backend/conexion.php';

$prestamos = $pdo->query(
    "SELECT p.id_prestamo, p.pr_f_pres, p.pr_f_dev,
            so.so_nombre, so.so_apellido, so.so_cedula,
            l.li_titulo, sd.se_nombre
     FROM prestamo p
     INNER JOIN ejemplar e ON e.id_ejemplar = p.id_ejemplar
     INNER JOIN libro l ON l.id_libro = e.id_libro
     INNER JOIN sede_biblioteca sd ON sd.id_sede = e.id_sede
     INNER JOIN socio so ON so.id_socio = p.id_socio
     ORDER BY (p.pr_f_dev IS NULL) DESC, p.pr_f_pres DESC"
)->fetchAll();

$prestamosActivos  = array_filter($prestamos, fn($p) => $p['pr_f_dev'] === null);
$prestamosDevueltos = array_filter($prestamos, fn($p) => $p['pr_f_dev'] !== null);

$socios = $pdo->query(
    "SELECT id_socio, so_nombre, so_apellido, so_cedula FROM socio ORDER BY so_nombre, so_apellido"
)->fetchAll();

// Solo se pueden prestar ejemplares que estén Disponibles
$ejemplaresDisponibles = $pdo->query(
    "SELECT e.id_ejemplar, l.li_titulo, sd.se_nombre
     FROM ejemplar e
     INNER JOIN libro l ON l.id_libro = e.id_libro
     INNER JOIN sede_biblioteca sd ON sd.id_sede = e.id_sede
     WHERE e.ej_estado = 'Disponible'
     ORDER BY l.li_titulo"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préstamos · Biblioteca Municipal</title>
    <link rel="stylesheet" href="frontend/css/estilo.css">
</head>
<body>
    <?php include 'backend/includes/navbar.php'; ?>

    <main class="lib-contenido">
        <div class="lib-cabecera-seccion">
            <h2>Préstamos</h2>
            <button type="button" class="lib-btn-primario lib-btn-ancho-auto" onclick="abrirModalNuevo()">+ Nuevo préstamo</button>
        </div>

        <?php
        $mensajes = [
            'noselecciono'  => 'Debes seleccionar un socio y un ejemplar disponible.',
            'nodisponible'  => 'Ese ejemplar ya no está disponible (alguien más lo tomó primero).',
        ];
        $clave = $_GET['error'] ?? null;
        if ($clave && isset($mensajes[$clave])):
        ?>
            <div class="lib-alerta"><?= htmlspecialchars($mensajes[$clave]) ?></div>
        <?php endif; ?>

        <?php if (empty($prestamos)): ?>
            <p class="lib-vacio">Todavía no hay préstamos registrados.</p>
        <?php else: ?>

        <div class="lib-pestanas">
            <button type="button" id="tabBtnActivos" class="lib-pestana lib-pestana--activa" onclick="cambiarPestana('activos')">
                Activos <span class="lib-pestana__contador"><?= count($prestamosActivos) ?></span>
            </button>
            <button type="button" id="tabBtnDevueltos" class="lib-pestana" onclick="cambiarPestana('devueltos')">
                Devueltos <span class="lib-pestana__contador"><?= count($prestamosDevueltos) ?></span>
            </button>
        </div>

        <!-- ---------- Pestaña: Activos ---------- -->
        <div id="tabActivos" class="lib-tabla-wrap">
            <?php if (empty($prestamosActivos)): ?>
                <p class="lib-vacio lib-vacio--con-padding">No hay préstamos activos.</p>
            <?php else: ?>
            <table class="lib-tabla">
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Sede</th>
                        <th>Socio</th>
                        <th>F. préstamo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamosActivos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['li_titulo']) ?></td>
                        <td><?= htmlspecialchars($p['se_nombre']) ?></td>
                        <td><?= htmlspecialchars($p['so_nombre'] . ' ' . $p['so_apellido']) ?> <small class="lib-ayuda">(<?= htmlspecialchars($p['so_cedula']) ?>)</small></td>
                        <td><?= htmlspecialchars($p['pr_f_pres']) ?></td>
                        <td class="lib-acciones">
                            <form action="backend/prestamos_devolver.php" method="POST" class="lib-form-inline"
                                  onsubmit="return confirm('¿Marcar este préstamo como devuelto?');">
                                <input type="hidden" name="id_prestamo" value="<?= (int)$p['id_prestamo'] ?>">
                                <button type="submit" class="lib-btn-editar">Devolver</button>
                            </form>
                            <form action="backend/prestamos_eliminar.php" method="POST" class="lib-form-inline"
                                  onsubmit="return confirm('¿Eliminar este registro de préstamo?');">
                                <input type="hidden" name="id_prestamo" value="<?= (int)$p['id_prestamo'] ?>">
                                <button type="submit" class="lib-btn-eliminar">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- ---------- Pestaña: Devueltos ---------- -->
        <div id="tabDevueltos" class="lib-tabla-wrap" style="display:none;">
            <?php if (empty($prestamosDevueltos)): ?>
                <p class="lib-vacio lib-vacio--con-padding">No hay préstamos devueltos todavía.</p>
            <?php else: ?>
            <table class="lib-tabla">
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Sede</th>
                        <th>Socio</th>
                        <th>F. préstamo</th>
                        <th>F. devolución</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamosDevueltos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['li_titulo']) ?></td>
                        <td><?= htmlspecialchars($p['se_nombre']) ?></td>
                        <td><?= htmlspecialchars($p['so_nombre'] . ' ' . $p['so_apellido']) ?> <small class="lib-ayuda">(<?= htmlspecialchars($p['so_cedula']) ?>)</small></td>
                        <td><?= htmlspecialchars($p['pr_f_pres']) ?></td>
                        <td><?= htmlspecialchars($p['pr_f_dev']) ?></td>
                        <td class="lib-acciones">
                            <form action="backend/prestamos_eliminar.php" method="POST" class="lib-form-inline lib-form-inline--ancho"
                                  onsubmit="return confirm('¿Eliminar este registro de préstamo?');">
                                <input type="hidden" name="id_prestamo" value="<?= (int)$p['id_prestamo'] ?>">
                                <button type="submit" class="lib-btn-eliminar lib-btn-eliminar--ancho">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <!-- ================= MODAL NUEVO PRÉSTAMO ================= -->
    <div id="modalPrestamo" class="lib-modal-fondo">
        <div class="lib-modal lib-modal--angosto">
            <div class="lib-modal__cabecera">
                <h3>Nuevo préstamo</h3>
                <button type="button" class="lib-modal__cerrar" onclick="cerrarModal()">&times;</button>
            </div>

            <form action="backend/prestamos_guardar.php" method="POST">
                <label for="id_socio">Socio</label>
                <select id="id_socio" name="id_socio" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($socios as $s): ?>
                        <option value="<?= (int)$s['id_socio'] ?>">
                            <?= htmlspecialchars($s['so_nombre'] . ' ' . $s['so_apellido'] . ' — ' . $s['so_cedula']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="id_ejemplar">Ejemplar disponible</label>
                <select id="id_ejemplar" name="id_ejemplar" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($ejemplaresDisponibles as $e): ?>
                        <option value="<?= (int)$e['id_ejemplar'] ?>">
                            <?= htmlspecialchars($e['li_titulo'] . ' — ' . $e['se_nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($ejemplaresDisponibles)): ?>
                    <p class="lib-ayuda">No hay ejemplares disponibles en este momento.</p>
                <?php endif; ?>

                <p class="lib-ayuda">La fecha de préstamo se registra automáticamente con la fecha de hoy.</p>

                <button type="submit" class="lib-btn-primario" style="margin-top:1rem;">Registrar préstamo</button>
            </form>
        </div>
    </div>

    <script>
        function cambiarPestana(cual) {
            const activos = cual === 'activos';
            document.getElementById('tabActivos').style.display = activos ? 'block' : 'none';
            document.getElementById('tabDevueltos').style.display = activos ? 'none' : 'block';
            document.getElementById('tabBtnActivos').classList.toggle('lib-pestana--activa', activos);
            document.getElementById('tabBtnDevueltos').classList.toggle('lib-pestana--activa', !activos);
        }

        function abrirModal() {
            document.getElementById('modalPrestamo').classList.add('lib-modal-fondo--visible');
        }
        function cerrarModal() {
            document.getElementById('modalPrestamo').classList.remove('lib-modal-fondo--visible');
        }
        function abrirModalNuevo() {
            document.getElementById('id_socio').value = '';
            document.getElementById('id_ejemplar').value = '';
            abrirModal();
        }
    </script>
</body>
</html>
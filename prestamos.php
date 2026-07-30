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
    "SELECT p.id_prestamo, p.pr_f_pres, p.pr_f_dev_esperada, p.pr_f_dev_real,
            so.so_nombre, so.so_apellido, so.so_cedula,
            l.li_titulo, sd.se_nombre
     FROM prestamo p
     INNER JOIN ejemplar e ON e.id_ejemplar = p.id_ejemplar
     INNER JOIN libro l ON l.id_libro = e.id_libro
     INNER JOIN sede_biblioteca sd ON sd.id_sede = e.id_sede
     INNER JOIN socio so ON so.id_socio = p.id_socio
     ORDER BY (p.pr_f_dev_real IS NULL) DESC, p.pr_f_pres DESC"
)->fetchAll();

$prestamosActivos   = array_filter($prestamos, fn($p) => $p['pr_f_dev_real'] === null);
$prestamosDevueltos = array_filter($prestamos, fn($p) => $p['pr_f_dev_real'] !== null);

$sedes = $pdo->query(
    "SELECT id_sede, se_nombre FROM sede_biblioteca ORDER BY se_nombre"
)->fetchAll();

$socios = $pdo->query(
    "SELECT id_socio, so_nombre, so_apellido, so_cedula FROM socio ORDER BY so_nombre, so_apellido"
)->fetchAll();

// Solo se pueden prestar ejemplares que estén Disponibles.
// Se traen todos (de todas las sedes) y el JS del modal filtra por sede
// una vez que el usuario elige la sede en el paso 1.
$ejemplaresDisponibles = $pdo->query(
    "SELECT e.id_ejemplar, e.id_sede, l.li_titulo
     FROM ejemplar e
     INNER JOIN libro l ON l.id_libro = e.id_libro
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
            'noselecciono'  => 'Debes completar la sede, el ejemplar, el socio y la fecha de devolución.',
            'nodisponible'  => 'Ese ejemplar ya no está disponible (alguien más lo tomó primero).',
            'fechainvalida' => 'La fecha de devolución no es válida: no puede ser hoy ni una fecha anterior.',
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
                        <th>F. dev. esperada</th>
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
                        <td><?= htmlspecialchars($p['pr_f_dev_esperada']) ?></td>
                        <td class="lib-acciones">
                            <form action="backend/prestamos_devolver.php" method="POST" class="lib-form-inline"
                                  onsubmit="return confirm('¿Marcar este préstamo como devuelto?');">
                                <input type="hidden" name="id_prestamo" value="<?= htmlspecialchars($p['id_prestamo'], ENT_QUOTES) ?>">
                                <button type="submit" class="lib-btn-editar">Devolver</button>
                            </form>
                            <form action="backend/prestamos_eliminar.php" method="POST" class="lib-form-inline"
                                  onsubmit="return confirm('¿Eliminar este registro de préstamo?');">
                                <input type="hidden" name="id_prestamo" value="<?= htmlspecialchars($p['id_prestamo'], ENT_QUOTES) ?>">
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
                        <th>F. dev. esperada</th>
                        <th>F. devolución real</th>
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
                        <td><?= htmlspecialchars($p['pr_f_dev_esperada']) ?></td>
                        <td><?= htmlspecialchars($p['pr_f_dev_real']) ?></td>
                        <td class="lib-acciones">
                            <form action="backend/prestamos_eliminar.php" method="POST" class="lib-form-inline lib-form-inline--ancho"
                                  onsubmit="return confirm('¿Eliminar este registro de préstamo?');">
                                <input type="hidden" name="id_prestamo" value="<?= htmlspecialchars($p['id_prestamo'], ENT_QUOTES) ?>">
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

            <form action="backend/prestamos_guardar.php" method="POST" id="formPrestamo">
                <!-- Paso 1: primero se elige la sede -->
                <label for="modal_id_sede">Sede</label>
                <select id="modal_id_sede" name="id_sede" required onchange="cambiarSede()">
                    <option value="">Seleccione una sede…</option>
                    <?php foreach ($sedes as $sd): ?>
                        <option value="<?= htmlspecialchars($sd['id_sede'], ENT_QUOTES) ?>">
                            <?= htmlspecialchars($sd['se_nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Paso 2: solo aparece cuando ya hay una sede elegida -->
                <div id="pasoDosPrestamo" style="display:none;">
                    <label for="modal_id_ejemplar">Ejemplar disponible en esta sede</label>
                    <select id="modal_id_ejemplar" name="id_ejemplar">
                        <option value="">Seleccione…</option>
                    </select>
                    <p id="avisoSinEjemplares" class="lib-ayuda" style="display:none;">No hay ejemplares disponibles en esta sede.</p>

                    <label for="modal_id_socio">Socio</label>
                    <select id="modal_id_socio" name="id_socio">
                        <option value="">Seleccione…</option>
                        <?php foreach ($socios as $s): ?>
                            <option value="<?= htmlspecialchars($s['id_socio'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($s['so_nombre'] . ' ' . $s['so_apellido'] . ' — ' . $s['so_cedula']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="modal_f_dev">Fecha de devolución</label>
                    <input type="date" id="modal_f_dev" name="pr_f_dev_esperada">
                    <p class="lib-ayuda">La fecha de préstamo se registra automáticamente con la de hoy. No se pueden elegir fechas de hoy hacia atrás en el calendario.</p>
                    <p id="avisoFecha" class="lib-alerta" style="display:none; margin-top:0.4rem;">Elige una fecha de devolución válida (no puede ser hoy ni una fecha anterior).</p>

                    <button type="submit" class="lib-btn-primario" style="margin-top:1rem;">Registrar préstamo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Ejemplares disponibles de TODAS las sedes; el modal los filtra en el navegador
        // según la sede que el usuario elija en el paso 1 (sin recargar la página).
        const ejemplaresPorSede = <?= json_encode($ejemplaresDisponibles, JSON_UNESCAPED_UNICODE) ?>;

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
            document.getElementById('modal_id_sede').value = '';
            document.getElementById('modal_id_socio').value = '';
            document.getElementById('modal_f_dev').value = '';
            document.getElementById('avisoFecha').style.display = 'none';
            document.getElementById('pasoDosPrestamo').style.display = 'none';
            abrirModal();
        }

        // Paso 1 -> Paso 2: al elegir la sede, se rellena el select de ejemplares
        // solo con los disponibles de esa sede y se revela el resto del formulario.
        function cambiarSede() {
            const idSede = document.getElementById('modal_id_sede').value;
            const pasoDos = document.getElementById('pasoDosPrestamo');
            const selectEjemplar = document.getElementById('modal_id_ejemplar');
            const avisoSinEjemplares = document.getElementById('avisoSinEjemplares');

            selectEjemplar.innerHTML = '<option value="">Seleccione…</option>';

            if (!idSede) {
                pasoDos.style.display = 'none';
                return;
            }

            const disponibles = ejemplaresPorSede.filter(e => e.id_sede === idSede);

            disponibles.forEach(e => {
                const opcion = document.createElement('option');
                opcion.value = e.id_ejemplar;
                opcion.textContent = e.li_titulo;
                selectEjemplar.appendChild(opcion);
            });

            avisoSinEjemplares.style.display = disponibles.length === 0 ? 'block' : 'none';

            selectEjemplar.required = true;
            document.getElementById('modal_id_socio').required = true;
            document.getElementById('modal_f_dev').required = true;

            pasoDos.style.display = 'block';
        }

        // No se pueden elegir fechas pasadas: se bloquean directamente en el
        // propio calendario del navegador con el atributo "min" (igual que en
        // los formularios de cédula), en vez de dejar elegir y luego avisar.
        function fechaMinimaPermitida() {
            const manana = new Date();
            manana.setDate(manana.getDate() + 1);
            const año = manana.getFullYear();
            const mes = String(manana.getMonth() + 1).padStart(2, '0');
            const dia = String(manana.getDate()).padStart(2, '0');
            return `${año}-${mes}-${dia}`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('modal_f_dev').setAttribute('min', fechaMinimaPermitida());
        });

        const campoFechaDev = document.getElementById('modal_f_dev');
        campoFechaDev.addEventListener('change', function () {
            const aviso = document.getElementById('avisoFecha');
            if (this.value && this.value < this.min) {
                this.value = '';
                aviso.style.display = 'block';
            } else {
                aviso.style.display = 'none';
            }
        });

        // Segunda barrera por si el navegador permitiera escribir la fecha a mano.
        document.getElementById('formPrestamo').addEventListener('submit', function (e) {
            const fecha = document.getElementById('modal_f_dev');
            if (fecha.offsetParent !== null && fecha.value < fecha.min) {
                e.preventDefault();
                document.getElementById('avisoFecha').style.display = 'block';
            }
        });
    </script>
</body>
</html>
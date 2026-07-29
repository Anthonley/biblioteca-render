<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];

require_once 'backend/conexion.php';

$ejemplares = $pdo->query(
    "SELECT e.id_ejemplar, e.ej_estado, e.id_libro, e.id_sede,
            l.li_titulo, s.se_nombre
     FROM ejemplar e
     INNER JOIN libro l ON l.id_libro = e.id_libro
     INNER JOIN sede_biblioteca s ON s.id_sede = e.id_sede
     ORDER BY l.li_titulo, s.se_nombre"
)->fetchAll();

$libros = $pdo->query("SELECT id_libro, li_titulo FROM libro ORDER BY li_titulo")->fetchAll();
$sedes  = $pdo->query("SELECT id_sede, se_nombre FROM sede_biblioteca ORDER BY se_nombre")->fetchAll();

// Estados que se pueden asignar a mano. "Prestado" ya NO está aquí:
// ese estado ahora lo controla automáticamente el flujo de Préstamos.
$estados = ['Disponible', 'En Reparación', 'Extraviado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplares · Biblioteca Municipal</title>
    <link rel="stylesheet" href="frontend/css/estilo.css">
</head>
<body>
    <?php include 'backend/includes/navbar.php'; ?>

    <main class="lib-contenido">
        <div class="lib-cabecera-seccion">
            <h2>Ejemplares</h2>
            <button type="button" class="lib-btn-primario lib-btn-ancho-auto" onclick="abrirModalNuevo()">+ Agregar ejemplar</button>
        </div>

        <?php
        $mensajes = [
            'prestamoactivo'    => ['tipo' => 'error', 'texto' => 'No se puede eliminar: este ejemplar tiene un préstamo activo (sin devolver).'],
            'camposobligatorios'=> ['tipo' => 'error', 'texto' => 'Debes seleccionar libro, sede y estado.'],
        ];
        $clave = $_GET['error'] ?? null;
        if ($clave && isset($mensajes[$clave])):
            $m = $mensajes[$clave];
        ?>
            <div class="lib-alerta"><?= htmlspecialchars($m['texto']) ?></div>
        <?php endif; ?>

        <?php if (empty($ejemplares)): ?>
            <p class="lib-vacio">Todavía no hay ejemplares registrados.</p>
        <?php else: ?>
        <div class="lib-tabla-wrap">
            <table class="lib-tabla">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Libro</th>
                        <th>Sede</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ejemplares as $ej): ?>
                    <tr>
                        <td><?= (int)$ej['id_ejemplar'] ?></td>
                        <td><?= htmlspecialchars($ej['li_titulo']) ?></td>
                        <td><?= htmlspecialchars($ej['se_nombre']) ?></td>
                        <td><span class="lib-estado lib-estado--<?= strtolower(str_replace(' ', '-', $ej['ej_estado'])) ?>"><?= htmlspecialchars($ej['ej_estado']) ?></span></td>
                        <td class="lib-acciones">
                            <?php if ($ej['ej_estado'] === 'Prestado'): ?>
                                <button type="button" class="lib-btn-editar lib-btn-editar--deshabilitado"
                                    onclick="mostrarAviso('Este ejemplar está prestado: gestiónalo desde Préstamos.')">Editar</button>
                                <button type="button" class="lib-btn-eliminar lib-btn-eliminar--deshabilitado"
                                    onclick="mostrarAviso('No se puede eliminar un ejemplar actualmente prestado.')">Eliminar</button>
                            <?php else: ?>
                            <button type="button" class="lib-btn-editar"
                                onclick="abrirModalEditar(
                                    <?= (int)$ej['id_ejemplar'] ?>,
                                    '<?= htmlspecialchars($ej['id_libro'], ENT_QUOTES) ?>',
                                    <?= (int)$ej['id_sede'] ?>,
                                    '<?= htmlspecialchars($ej['ej_estado'], ENT_QUOTES) ?>'
                                )">Editar</button>

                            <form action="backend/ejemplares_eliminar.php" method="POST" class="lib-form-inline"
                                  onsubmit="return confirm('¿Eliminar este ejemplar?');">
                                <input type="hidden" name="id_ejemplar" value="<?= (int)$ej['id_ejemplar'] ?>">
                                <button type="submit" class="lib-btn-eliminar">Eliminar</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>

    <!-- ================= MODAL EJEMPLAR ================= -->
    <div id="modalEjemplar" class="lib-modal-fondo">
        <div class="lib-modal lib-modal--angosto">
            <div class="lib-modal__cabecera">
                <h3 id="modalTitulo">Agregar ejemplar</h3>
                <button type="button" class="lib-modal__cerrar" onclick="cerrarModal()">&times;</button>
            </div>

            <form action="backend/ejemplares_guardar.php" method="POST">
                <input type="hidden" name="id_ejemplar" id="id_ejemplar" value="">

                <label for="id_libro">Libro</label>
                <select id="id_libro" name="id_libro" required>
                    <?php foreach ($libros as $l): ?>
                        <option value="<?= htmlspecialchars($l['id_libro'], ENT_QUOTES) ?>"><?= htmlspecialchars($l['li_titulo']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="id_sede">Sede</label>
                <select id="id_sede" name="id_sede" required>
                    <?php foreach ($sedes as $s): ?>
                        <option value="<?= (int)$s['id_sede'] ?>"><?= htmlspecialchars($s['se_nombre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="ej_estado">Estado</label>
                <select id="ej_estado" name="ej_estado" required>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= htmlspecialchars($e) ?>"><?= htmlspecialchars($e) ?></option>
                    <?php endforeach; ?>
                </select>

                <div id="grupoCantidad">
                    <label for="cantidad">Cantidad de copias a crear</label>
                    <input type="number" id="cantidad" name="cantidad" min="1" max="50" value="1">
                </div>

                <button type="submit" class="lib-btn-primario" style="margin-top:1rem;">Guardar</button>
            </form>
        </div>
    </div>

    <div id="aviso" class="lib-aviso"></div>

    <script>
        let avisoTimeout;
        function mostrarAviso(texto) {
            const aviso = document.getElementById('aviso');
            aviso.textContent = texto;
            aviso.classList.add('lib-aviso--visible');
            clearTimeout(avisoTimeout);
            avisoTimeout = setTimeout(() => aviso.classList.remove('lib-aviso--visible'), 3000);
        }

        function abrirModal() {
            document.getElementById('modalEjemplar').classList.add('lib-modal-fondo--visible');
        }
        function cerrarModal() {
            document.getElementById('modalEjemplar').classList.remove('lib-modal-fondo--visible');
        }

        function abrirModalNuevo() {
            document.getElementById('modalTitulo').textContent = 'Agregar ejemplar';
            document.getElementById('id_ejemplar').value = '';
            document.getElementById('id_libro').selectedIndex = 0;
            document.getElementById('id_sede').selectedIndex = 0;
            document.getElementById('ej_estado').value = 'Disponible';
            document.getElementById('cantidad').value = 1;
            document.getElementById('grupoCantidad').style.display = 'block';
            abrirModal();
        }

        function abrirModalEditar(id, idLibro, idSede, estado) {
            document.getElementById('modalTitulo').textContent = 'Editar ejemplar';
            document.getElementById('id_ejemplar').value = id;
            document.getElementById('id_libro').value = idLibro;
            document.getElementById('id_sede').value = idSede;
            document.getElementById('ej_estado').value = estado;
            // Al editar es UN ejemplar puntual: no tiene sentido "cantidad"
            document.getElementById('cantidad').value = 1;
            document.getElementById('grupoCantidad').style.display = 'none';
            abrirModal();
        }
    </script>
</body>
</html>
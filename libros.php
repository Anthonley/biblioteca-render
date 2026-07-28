<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];

require_once 'backend/conexion.php';

// Libros + sus géneros (concatenados) para la tabla
$libros = $pdo->query(
    "SELECT l.id_libro, l.li_titulo, l.li_autor, l.li_editorial,
            GROUP_CONCAT(g.ge_nombre ORDER BY g.ge_nombre SEPARATOR ', ') AS generos_nombres,
            GROUP_CONCAT(g.id_genero ORDER BY g.id_genero SEPARATOR ',')  AS generos_ids
     FROM libro l
     LEFT JOIN tag t ON t.id_libro = l.id_libro
     LEFT JOIN genero g ON g.id_genero = t.id_genero
     GROUP BY l.id_libro
     ORDER BY l.li_titulo"
)->fetchAll();

// Catálogo completo de géneros (para el <select> del modal)
$generos = $pdo->query("SELECT id_genero, ge_nombre FROM genero ORDER BY ge_nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libros · Biblioteca Municipal</title>
    <link rel="stylesheet" href="frontend/css/estilo.css">
</head>
<body>
    <?php include 'backend/includes/navbar.php'; ?>

    <main class="lib-contenido">
        <div class="lib-cabecera-seccion">
            <h2>Libros</h2>
            <div class="lib-cabecera-seccion__botones">
                <button type="button" class="lib-btn-secundario lib-btn-ancho-auto" onclick="abrirModalGenero()">+ Añadir género</button>
                <button type="button" class="lib-btn-primario lib-btn-ancho-auto" onclick="abrirModalNuevo()">+ Agregar libro</button>
            </div>
        </div>

        <?php
        $mensajes = [
            'prestamosactivos'  => ['tipo' => 'error', 'texto' => 'No se puede eliminar: este libro tiene préstamos activos (sin devolver).'],
            'camposobligatorios'=> ['tipo' => 'error', 'texto' => 'Título y autor son obligatorios.'],
            'generovacio'       => ['tipo' => 'error', 'texto' => 'El nombre del género no puede estar vacío.'],
            'generoduplicado'   => ['tipo' => 'error', 'texto' => 'Ese género ya existe.'],
            'generocreado'      => ['tipo' => 'ok',    'texto' => 'Género creado correctamente.'],
        ];
        $clave = $_GET['error'] ?? $_GET['ok'] ?? null;
        if ($clave && isset($mensajes[$clave])):
            $m = $mensajes[$clave];
        ?>
            <div class="lib-alerta <?= $m['tipo'] === 'ok' ? 'lib-alerta--ok' : '' ?>"><?= htmlspecialchars($m['texto']) ?></div>
        <?php endif; ?>

        <?php if (empty($libros)): ?>
            <p class="lib-vacio">Todavía no hay libros registrados.</p>
        <?php else: ?>
        <div class="lib-tabla-wrap">
            <table class="lib-tabla">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Editorial</th>
                        <th>Géneros</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($libros as $libro): ?>
                    <tr>
                        <td><?= htmlspecialchars($libro['li_titulo']) ?></td>
                        <td><?= htmlspecialchars($libro['li_autor']) ?></td>
                        <td><?= htmlspecialchars($libro['li_editorial'] ?? '—') ?></td>
                        <td>
                            <?php if ($libro['generos_nombres']): ?>
                                <?php foreach (explode(', ', $libro['generos_nombres']) as $g): ?>
                                    <span class="lib-chip"><?= htmlspecialchars($g) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="lib-vacio">Sin género</span>
                            <?php endif; ?>
                        </td>
                        <td class="lib-acciones">
                            <button type="button" class="lib-btn-editar"
                                onclick="abrirModalEditar(
                                    <?= (int)$libro['id_libro'] ?>,
                                    '<?= htmlspecialchars($libro['li_titulo'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($libro['li_autor'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($libro['li_editorial'] ?? '', ENT_QUOTES) ?>',
                                    '<?= $libro['generos_ids'] ?? '' ?>'
                                )">Editar</button>

                            <form action="backend/libros_eliminar.php" method="POST" class="lib-form-inline"
                                  onsubmit="return confirm('¿Eliminar este libro? Esto también eliminará sus ejemplares y préstamos asociados.');">
                                <input type="hidden" name="id_libro" value="<?= (int)$libro['id_libro'] ?>">
                                <button type="submit" class="lib-btn-eliminar">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>

    <!-- ================= MODAL LIBRO ================= -->
    <div id="modalLibro" class="lib-modal-fondo">
        <div class="lib-modal">
            <div class="lib-modal__cabecera">
                <h3 id="modalTitulo">Agregar libro</h3>
                <button type="button" class="lib-modal__cerrar" onclick="cerrarModal()">&times;</button>
            </div>

            <form id="formLibro" action="backend/libros_guardar.php" method="POST">
                <input type="hidden" name="id_libro" id="id_libro" value="">

                <label for="li_titulo">Título</label>
                <input type="text" id="li_titulo" name="li_titulo" required>

                <label for="li_autor">Autor</label>
                <input type="text" id="li_autor" name="li_autor" required>

                <label for="li_editorial">Editorial</label>
                <input type="text" id="li_editorial" name="li_editorial">

                <label>Géneros</label>
                <div class="lib-generos-selector">
                    <select id="selectGenero">
                        <?php foreach ($generos as $g): ?>
                            <option value="<?= (int)$g['id_genero'] ?>"><?= htmlspecialchars($g['ge_nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="lib-btn-secundario" onclick="agregarTag()">+ Añadir tag</button>
                </div>

                <div id="listaTags" class="lib-tags-lista"></div>
                <div id="tagsHidden"></div>

                <button type="submit" class="lib-btn-primario" style="margin-top:1.2rem;">Guardar libro</button>
            </form>
        </div>
    </div>

    <!-- ================= MODAL GÉNERO ================= -->
    <div id="modalGenero" class="lib-modal-fondo">
        <div class="lib-modal lib-modal--angosto">
            <div class="lib-modal__cabecera">
                <h3>Añadir género</h3>
                <button type="button" class="lib-modal__cerrar" onclick="cerrarModalGenero()">&times;</button>
            </div>

            <form action="backend/generos_guardar.php" method="POST">
                <label for="ge_nombre">Nombre del género</label>
                <input type="text" id="ge_nombre" name="ge_nombre" required>
                <p class="lib-ayuda">No se permiten géneros repetidos (sin distinguir mayúsculas).</p>

                <button type="submit" class="lib-btn-primario" style="margin-top:1rem;">Guardar género</button>
            </form>
        </div>
    </div>

    <script>
        // Nombre de cada género por id, para poder pintar los chips al editar
        const generosMap = {
            <?php foreach ($generos as $g): ?>
                <?= (int)$g['id_genero'] ?>: <?= json_encode($g['ge_nombre'], JSON_UNESCAPED_UNICODE) ?>,
            <?php endforeach; ?>
        };

        let tagsSeleccionados = []; // array de ids (number) seleccionados en el modal

        function renderTags() {
            const lista = document.getElementById('listaTags');
            const hidden = document.getElementById('tagsHidden');
            lista.innerHTML = '';
            hidden.innerHTML = '';

            tagsSeleccionados.forEach(id => {
                const chip = document.createElement('span');
                chip.className = 'lib-chip lib-chip--removible';
                chip.innerHTML = (generosMap[id] ?? '?') + ' <button type="button" onclick="quitarTag(' + id + ')">&times;</button>';
                lista.appendChild(chip);

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'generos[]';
                input.value = id;
                hidden.appendChild(input);
            });
        }

        function agregarTag() {
            const select = document.getElementById('selectGenero');
            const id = parseInt(select.value, 10);
            if (!tagsSeleccionados.includes(id)) {
                tagsSeleccionados.push(id);
                renderTags();
            }
        }

        function quitarTag(id) {
            tagsSeleccionados = tagsSeleccionados.filter(t => t !== id);
            renderTags();
        }

        function abrirModal() {
            document.getElementById('modalLibro').classList.add('lib-modal-fondo--visible');
        }

        function cerrarModal() {
            document.getElementById('modalLibro').classList.remove('lib-modal-fondo--visible');
        }

        function abrirModalNuevo() {
            document.getElementById('modalTitulo').textContent = 'Agregar libro';
            document.getElementById('formLibro').reset();
            document.getElementById('id_libro').value = '';
            tagsSeleccionados = [];
            renderTags();
            abrirModal();
        }

        function abrirModalEditar(id, titulo, autor, editorial, generosIdsCsv) {
            document.getElementById('modalTitulo').textContent = 'Editar libro';
            document.getElementById('id_libro').value = id;
            document.getElementById('li_titulo').value = titulo;
            document.getElementById('li_autor').value = autor;
            document.getElementById('li_editorial').value = editorial;

            tagsSeleccionados = generosIdsCsv
                ? generosIdsCsv.split(',').map(v => parseInt(v, 10))
                : [];
            renderTags();
            abrirModal();
        }

        function abrirModalGenero() {
            document.getElementById('modalGenero').classList.add('lib-modal-fondo--visible');
        }

        function cerrarModalGenero() {
            document.getElementById('modalGenero').classList.remove('lib-modal-fondo--visible');
        }
    </script>
</body>
</html>
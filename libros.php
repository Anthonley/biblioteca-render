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
    "SELECT l.id_libro, l.li_titulo, l.li_autor, l.li_editorial, l.li_isbn,
            STRING_AGG(g.ge_nombre, ', ' ORDER BY g.ge_nombre) AS generos_nombres,
            STRING_AGG(g.id_genero::text, ',' ORDER BY g.id_genero) AS generos_ids
     FROM libro l
     LEFT JOIN tag t ON t.id_libro = l.id_libro
     LEFT JOIN genero g ON g.id_genero = t.id_genero
     GROUP BY l.id_libro
     ORDER BY l.li_titulo"
)->fetchAll();

// Catálogo completo de géneros (para el <select> del modal)
$generos = $pdo->query("SELECT id_genero, ge_nombre FROM genero ORDER BY ge_nombre")->fetchAll();

// Siguiente ID sugerido (LI-0001, LI-0002, ...). El usuario puede editarlo
// antes de guardar; se valida también del lado del servidor.
$maxNum = (int) $pdo->query(
    "SELECT COALESCE(MAX(CAST(SUBSTRING(id_libro FROM 4) AS INTEGER)), 0) FROM libro"
)->fetchColumn();
$siguienteIdLibro = 'LI-' . str_pad((string)($maxNum + 1), 4, '0', STR_PAD_LEFT);
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
            'idinvalido'        => ['tipo' => 'error', 'texto' => 'El ID debe ser un número de hasta 4 dígitos (ej. 7 → LI-0007).'],
            'idduplicado'       => ['tipo' => 'error', 'texto' => 'Ese ID ya existe, usa otro.'],
            'autorinvalido'     => ['tipo' => 'error', 'texto' => 'El autor solo puede tener letras y espacios, con al menos 2 letras.'],
            'editorialinvalida' => ['tipo' => 'error', 'texto' => 'La editorial solo puede tener letras y espacios, con al menos 2 letras.'],
            'isbninvalido'      => ['tipo' => 'error', 'texto' => 'El ISBN no es válido (debe tener 10 o 13 dígitos).'],
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
                        <th>ID</th>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Editorial</th>
                        <th>ISBN</th>
                        <th>Géneros</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($libros as $libro): ?>
                    <tr>
                        <td><?= htmlspecialchars($libro['id_libro']) ?></td>
                        <td><?= htmlspecialchars($libro['li_titulo']) ?></td>
                        <td><?= htmlspecialchars($libro['li_autor']) ?></td>
                        <td><?= htmlspecialchars($libro['li_editorial'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($libro['li_isbn'] ?? '—') ?></td>
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
                                    '<?= htmlspecialchars($libro['id_libro'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($libro['li_titulo'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($libro['li_autor'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($libro['li_editorial'] ?? '', ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($libro['li_isbn'] ?? '', ENT_QUOTES) ?>',
                                    '<?= $libro['generos_ids'] ?? '' ?>'
                                )">Editar</button>

                            <form action="backend/libros_eliminar.php" method="POST" class="lib-form-inline"
                                  onsubmit="return confirm('¿Eliminar este libro? Esto también eliminará sus ejemplares y préstamos asociados.');">
                                <input type="hidden" name="id_libro" value="<?= htmlspecialchars($libro['id_libro'], ENT_QUOTES) ?>">
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
                <input type="hidden" name="id_libro_original" id="id_libro_original" value="">

                <label for="id_libro">ID</label>
                <input type="text" id="id_libro" name="id_libro" required
                       maxlength="7" placeholder="LI-0000"
                       value="<?= htmlspecialchars($siguienteIdLibro) ?>">

                <label for="li_titulo">Título</label>
                <input type="text" id="li_titulo" name="li_titulo" required>

                <label for="li_autor">Autor</label>
                <input type="text" id="li_autor" name="li_autor" placeholder="Dejar vacío = Anónimo">

                <label for="li_editorial">Editorial</label>
                <input type="text" id="li_editorial" name="li_editorial" placeholder="Opcional">

                <label for="li_isbn">ISBN</label>
                <input type="text" id="li_isbn" name="li_isbn" placeholder="Ej. 978-0-441-01359-3">
                <p class="lib-ayuda">Opcional. 10 o 13 dígitos (se ignoran guiones).</p>

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

                <p id="errorFormLibro" class="lib-alerta" style="display:none;"></p>

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

        const siguienteIdLibro = <?= json_encode($siguienteIdLibro, JSON_UNESCAPED_UNICODE) ?>;

        function abrirModalNuevo() {
            document.getElementById('modalTitulo').textContent = 'Agregar libro';
            document.getElementById('formLibro').reset();
            document.getElementById('id_libro_original').value = '';
            document.getElementById('id_libro').value = siguienteIdLibro;
            document.getElementById('id_libro').readOnly = false;
            ocultarErrorLibro();
            tagsSeleccionados = [];
            renderTags();
            abrirModal();
        }

        function abrirModalEditar(id, titulo, autor, editorial, isbn, generosIdsCsv) {
            document.getElementById('modalTitulo').textContent = 'Editar libro';
            document.getElementById('id_libro_original').value = id;
            document.getElementById('id_libro').value = id;
            document.getElementById('id_libro').readOnly = true;
            document.getElementById('li_titulo').value = titulo;
            document.getElementById('li_autor').value = autor;
            document.getElementById('li_editorial').value = editorial;
            document.getElementById('li_isbn').value = isbn;
            ocultarErrorLibro();

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

        // ---------- Validación antes de enviar ----------
        const soloLetras = /^[A-Za-zÀ-ÖØ-öø-ÿñÑ\s]+$/;

        function contarLetras(texto) {
            return (texto.match(/[A-Za-zÀ-ÖØ-öø-ÿñÑ]/g) || []).length;
        }

        // Acepta "LI-0007", "li-7" o solo "7" y siempre devuelve "LI-0007".
        // Devuelve null si no es válido (vacío o más de 4 dígitos).
        function normalizarIdLibro(valorCrudo) {
            let v = valorCrudo.trim().toUpperCase();
            if (v.startsWith('LI-')) v = v.slice(3);
            v = v.replace(/\D/g, '');
            if (v === '' || v.length > 4) return null;
            return 'LI-' + v.padStart(4, '0');
        }

        function mostrarErrorLibro(mensaje) {
            const p = document.getElementById('errorFormLibro');
            p.textContent = mensaje;
            p.style.display = 'block';
        }

        function ocultarErrorLibro() {
            const p = document.getElementById('errorFormLibro');
            p.style.display = 'none';
            p.textContent = '';
        }

        document.getElementById('formLibro').addEventListener('submit', function (e) {
            ocultarErrorLibro();

            const idInput = document.getElementById('id_libro');
            const idNormalizado = normalizarIdLibro(idInput.value);
            if (!idNormalizado) {
                e.preventDefault();
                return mostrarErrorLibro('El ID debe ser un número de hasta 4 dígitos (ej. 7 → LI-0007).');
            }
            idInput.value = idNormalizado;

            const titulo = document.getElementById('li_titulo').value.trim();
            const autor = document.getElementById('li_autor').value.trim();
            const editorial = document.getElementById('li_editorial').value.trim();
            const isbn = document.getElementById('li_isbn').value.trim();

            if (titulo === '') {
                e.preventDefault();
                return mostrarErrorLibro('El título no puede quedar vacío.');
            }

            if (autor !== '' && (!soloLetras.test(autor) || contarLetras(autor) < 2)) {
                e.preventDefault();
                return mostrarErrorLibro('El autor solo puede tener letras y espacios, con al menos 2 letras (o déjalo vacío para "Anónimo").');
            }

            if (editorial !== '' && (!soloLetras.test(editorial) || contarLetras(editorial) < 2)) {
                e.preventDefault();
                return mostrarErrorLibro('La editorial solo puede tener letras y espacios, con al menos 2 letras (o déjala vacía).');
            }

            if (isbn !== '') {
                const isbnLimpio = isbn.replace(/[\s-]/g, '');
                if (!/^\d{9}[\dXx]$/.test(isbnLimpio) && !/^\d{13}$/.test(isbnLimpio)) {
                    e.preventDefault();
                    return mostrarErrorLibro('El ISBN no es válido (debe tener 10 o 13 dígitos).');
                }
            }
        });
    </script>
</body>
</html>
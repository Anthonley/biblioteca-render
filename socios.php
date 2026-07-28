<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];

require_once 'backend/conexion.php';

$socios = $pdo->query(
    "SELECT id_socio, so_cedula, so_nombre, so_apellido, so_telefono
     FROM socio
     ORDER BY so_nombre, so_apellido"
)->fetchAll();

$paletaAvatares = ['#7c3248', '#8a6d3b', '#3b6e8a', '#4b7c3b', '#6d3b7c'];
$letras = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','Ñ','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Socios · Biblioteca Municipal</title>
    <link rel="stylesheet" href="frontend/css/estilo.css">
</head>
<body>
    <?php include 'backend/includes/navbar.php'; ?>

    <main class="lib-contenido">
        <div class="lib-cabecera-seccion">
            <h2>Socios</h2>
            <button type="button" class="lib-btn-primario lib-btn-ancho-auto" onclick="abrirModalNuevo()">+ Agregar socio</button>
        </div>

        <?php
        $mensajes = [
            'camposobligatorios' => 'Cédula, nombre y apellido son obligatorios.',
            'ceduladuplicada'    => 'Ya existe un socio registrado con esa cédula.',
            'prestamosactivos'   => 'No se puede eliminar: este socio tiene préstamos activos (sin devolver).',
        ];
        $clave = $_GET['error'] ?? null;
        if ($clave && isset($mensajes[$clave])):
        ?>
            <div class="lib-alerta"><?= htmlspecialchars($mensajes[$clave]) ?></div>
        <?php endif; ?>

        <?php if (empty($socios)): ?>
            <p class="lib-vacio">Todavía no hay socios registrados.</p>
        <?php else: ?>

        <!-- Buscador libre: nombre, apellido o cédula -->
        <div class="lib-buscador">
            <input type="text" id="buscadorSocios" placeholder="Buscar por nombre, apellido o cédula…" oninput="aplicarFiltros()">
        </div>

        <!-- Filtro alfabético -->
        <div class="lib-filtro-alfabeto">
            <div class="lib-filtro-fila">
                <span class="lib-filtro-etiqueta">Nombre</span>
                <div class="lib-filtro-botones">
                    <button type="button" class="lib-filtro-btn lib-filtro-btn--todos lib-filtro-btn--activo" data-campo="nombre" data-letra="TODOS" onclick="filtrarLetra(this)">Todos</button>
                    <?php foreach ($letras as $l): ?>
                        <button type="button" class="lib-filtro-btn" data-campo="nombre" data-letra="<?= $l ?>" onclick="filtrarLetra(this)"><?= $l ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="lib-filtro-fila">
                <span class="lib-filtro-etiqueta">Apellido(s)</span>
                <div class="lib-filtro-botones">
                    <button type="button" class="lib-filtro-btn lib-filtro-btn--todos lib-filtro-btn--activo" data-campo="apellido" data-letra="TODOS" onclick="filtrarLetra(this)">Todos</button>
                    <?php foreach ($letras as $l): ?>
                        <button type="button" class="lib-filtro-btn" data-campo="apellido" data-letra="<?= $l ?>" onclick="filtrarLetra(this)"><?= $l ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Todas las tarjetas juntas, sin separar por letra -->
        <div class="lib-socios-grid">
            <?php foreach ($socios as $s):
                $inicialNombre = mb_strtoupper(mb_substr($s['so_nombre'], 0, 1));
                $iniciales = mb_strtoupper(mb_substr($s['so_nombre'], 0, 1) . mb_substr($s['so_apellido'], 0, 1));
                $color = $paletaAvatares[ord($inicialNombre) % count($paletaAvatares)];
                $apellidoInicial = mb_strtoupper(mb_substr($s['so_apellido'], 0, 1));
                $busqueda = mb_strtolower($s['so_nombre'] . ' ' . $s['so_apellido'] . ' ' . $s['so_cedula']);
            ?>
            <div class="lib-socio-card"
                 data-nombre-inicial="<?= htmlspecialchars($inicialNombre) ?>"
                 data-apellido-inicial="<?= htmlspecialchars($apellidoInicial) ?>"
                 data-busqueda="<?= htmlspecialchars($busqueda) ?>">
                <div class="lib-socio-card__avatar" style="background: <?= $color ?>;"><?= htmlspecialchars($iniciales) ?></div>
                <div class="lib-socio-card__info">
                    <h4><?= htmlspecialchars($s['so_nombre'] . ' ' . $s['so_apellido']) ?></h4>
                    <p class="lib-socio-card__cedula">Cédula: <?= htmlspecialchars($s['so_cedula']) ?></p>
                    <p class="lib-socio-card__telefono"><?= htmlspecialchars($s['so_telefono'] ?? 'Sin teléfono registrado') ?></p>
                </div>
                <div class="lib-socio-card__acciones">
                    <button type="button" class="lib-btn-editar"
                        onclick="abrirModalEditar(
                            <?= (int)$s['id_socio'] ?>,
                            '<?= htmlspecialchars($s['so_cedula'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($s['so_nombre'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($s['so_apellido'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($s['so_telefono'] ?? '', ENT_QUOTES) ?>'
                        )">Editar</button>

                    <form action="backend/socios_eliminar.php" method="POST" class="lib-form-inline"
                          onsubmit="return confirm('¿Eliminar este socio?');">
                        <input type="hidden" name="id_socio" value="<?= (int)$s['id_socio'] ?>">
                        <button type="submit" class="lib-btn-eliminar">Eliminar</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <p id="avisoSinResultados" class="lib-vacio lib-vacio--con-padding" style="display:none;">Ningún socio coincide con el filtro.</p>
        <?php endif; ?>
    </main>

    <!-- ================= MODAL SOCIO ================= -->
    <div id="modalSocio" class="lib-modal-fondo">
        <div class="lib-modal lib-modal--angosto">
            <div class="lib-modal__cabecera">
                <h3 id="modalTitulo">Agregar socio</h3>
                <button type="button" class="lib-modal__cerrar" onclick="cerrarModal()">&times;</button>
            </div>

            <form action="backend/socios_guardar.php" method="POST">
                <input type="hidden" name="id_socio" id="id_socio" value="">

                <label for="so_cedula">Cédula</label>
                <input type="text" id="so_cedula" name="so_cedula" maxlength="10" required>

                <label for="so_nombre">Nombre</label>
                <input type="text" id="so_nombre" name="so_nombre" required>

                <label for="so_apellido">Apellido</label>
                <input type="text" id="so_apellido" name="so_apellido" required>

                <label for="so_telefono">Teléfono</label>
                <input type="text" id="so_telefono" name="so_telefono" maxlength="15">

                <button type="submit" class="lib-btn-primario" style="margin-top:1rem;">Guardar socio</button>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalSocio').classList.add('lib-modal-fondo--visible');
        }
        function cerrarModal() {
            document.getElementById('modalSocio').classList.remove('lib-modal-fondo--visible');
        }

        function abrirModalNuevo() {
            document.getElementById('modalTitulo').textContent = 'Agregar socio';
            document.getElementById('id_socio').value = '';
            document.getElementById('so_cedula').value = '';
            document.getElementById('so_nombre').value = '';
            document.getElementById('so_apellido').value = '';
            document.getElementById('so_telefono').value = '';
            abrirModal();
        }

        function abrirModalEditar(id, cedula, nombre, apellido, telefono) {
            document.getElementById('modalTitulo').textContent = 'Editar socio';
            document.getElementById('id_socio').value = id;
            document.getElementById('so_cedula').value = cedula;
            document.getElementById('so_nombre').value = nombre;
            document.getElementById('so_apellido').value = apellido;
            document.getElementById('so_telefono').value = telefono;
            abrirModal();
        }

        // ---------- Filtro alfabético + buscador (solo oculta/muestra tarjetas) ----------
        let filtroNombre = 'TODOS';
        let filtroApellido = 'TODOS';

        function filtrarLetra(boton) {
            const campo = boton.dataset.campo;
            const letra = boton.dataset.letra;

            if (campo === 'nombre') {
                filtroNombre = letra;
            } else {
                filtroApellido = letra;
            }

            boton.parentElement.querySelectorAll('.lib-filtro-btn').forEach(b => b.classList.remove('lib-filtro-btn--activo'));
            boton.classList.add('lib-filtro-btn--activo');

            aplicarFiltros();
        }

        function aplicarFiltros() {
            const texto = document.getElementById('buscadorSocios').value.trim().toLowerCase();
            const tarjetas = document.querySelectorAll('.lib-socio-card');
            let visibles = 0;

            tarjetas.forEach(tarjeta => {
                const coincideNombre   = filtroNombre === 'TODOS'   || tarjeta.dataset.nombreInicial === filtroNombre;
                const coincideApellido = filtroApellido === 'TODOS' || tarjeta.dataset.apellidoInicial === filtroApellido;
                const coincideTexto    = texto === '' || tarjeta.dataset.busqueda.includes(texto);

                const visible = coincideNombre && coincideApellido && coincideTexto;
                tarjeta.style.display = visible ? '' : 'none';
                if (visible) visibles++;
            });

            document.getElementById('avisoSinResultados').style.display = visibles === 0 ? '' : 'none';
        }
    </script>
</body>
</html>
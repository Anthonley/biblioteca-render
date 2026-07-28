<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['usuario_activo'])) {
    header('Location: index.php');
    exit();
}
$usuario = $_SESSION['usuario_activo'];

require_once 'backend/conexion.php';

$sedes = $pdo->query(
    "SELECT sd.id_sede, sd.se_nombre, sd.se_direccion,
            COUNT(e.id_ejemplar) AS total_ejemplares
     FROM sede_biblioteca sd
     LEFT JOIN ejemplar e ON e.id_sede = sd.id_sede
     GROUP BY sd.id_sede
     ORDER BY sd.se_nombre"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedes · Biblioteca Municipal</title>
    <link rel="stylesheet" href="frontend/css/estilo.css">
</head>
<body>
    <?php include 'backend/includes/navbar.php'; ?>

    <main class="lib-contenido">
        <div class="lib-cabecera-seccion">
            <h2>Sedes</h2>
            <button type="button" class="lib-btn-primario lib-btn-ancho-auto" onclick="abrirModalNuevo()">+ Agregar sede</button>
        </div>

        <?php
        $mensajes = [
            'camposobligatorios' => 'Nombre y dirección son obligatorios.',
            'nombreduplicado'    => 'Ya existe una sede con ese nombre.',
            'tieneejemplares'    => 'No se puede eliminar: esta sede tiene ejemplares asociados.',
        ];
        $clave = $_GET['error'] ?? null;
        if ($clave && isset($mensajes[$clave])):
        ?>
            <div class="lib-alerta"><?= htmlspecialchars($mensajes[$clave]) ?></div>
        <?php endif; ?>

        <?php if (empty($sedes)): ?>
            <p class="lib-vacio">Todavía no hay sedes registradas.</p>
        <?php else: ?>
        <div class="lib-sedes-grid">
            <?php foreach ($sedes as $s): ?>
            <div class="lib-sede-card">
                <div class="lib-sede-card__cabecera">
                    <span class="lib-sede-card__icono">🏛️</span>
                    <span class="lib-sede-card__badge"><?= (int)$s['total_ejemplares'] ?> ejemplar<?= (int)$s['total_ejemplares'] === 1 ? '' : 'es' ?></span>
                </div>
                <h4><?= htmlspecialchars($s['se_nombre']) ?></h4>
                <p class="lib-sede-card__direccion">📍 <?= htmlspecialchars($s['se_direccion']) ?></p>

                <div class="lib-sede-card__acciones">
                    <button type="button" class="lib-btn-editar"
                        onclick="abrirModalEditar(
                            <?= (int)$s['id_sede'] ?>,
                            '<?= htmlspecialchars($s['se_nombre'], ENT_QUOTES) ?>',
                            '<?= htmlspecialchars($s['se_direccion'], ENT_QUOTES) ?>'
                        )">Editar</button>

                    <form action="backend/sedes_eliminar.php" method="POST" class="lib-form-inline"
                          onsubmit="return confirm('¿Eliminar esta sede?');">
                        <input type="hidden" name="id_sede" value="<?= (int)$s['id_sede'] ?>">
                        <button type="submit" class="lib-btn-eliminar">Eliminar</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <!-- ================= MODAL SEDE ================= -->
    <div id="modalSede" class="lib-modal-fondo">
        <div class="lib-modal lib-modal--angosto">
            <div class="lib-modal__cabecera">
                <h3 id="modalTitulo">Agregar sede</h3>
                <button type="button" class="lib-modal__cerrar" onclick="cerrarModal()">&times;</button>
            </div>

            <form action="backend/sedes_guardar.php" method="POST">
                <input type="hidden" name="id_sede" id="id_sede" value="">

                <label for="se_nombre">Nombre</label>
                <input type="text" id="se_nombre" name="se_nombre" required>

                <label for="se_direccion">Dirección</label>
                <input type="text" id="se_direccion" name="se_direccion" required>

                <button type="submit" class="lib-btn-primario" style="margin-top:1rem;">Guardar sede</button>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalSede').classList.add('lib-modal-fondo--visible');
        }
        function cerrarModal() {
            document.getElementById('modalSede').classList.remove('lib-modal-fondo--visible');
        }

        function abrirModalNuevo() {
            document.getElementById('modalTitulo').textContent = 'Agregar sede';
            document.getElementById('id_sede').value = '';
            document.getElementById('se_nombre').value = '';
            document.getElementById('se_direccion').value = '';
            abrirModal();
        }

        function abrirModalEditar(id, nombre, direccion) {
            document.getElementById('modalTitulo').textContent = 'Editar sede';
            document.getElementById('id_sede').value = id;
            document.getElementById('se_nombre').value = nombre;
            document.getElementById('se_direccion').value = direccion;
            abrirModal();
        }
    </script>
</body>
</html>
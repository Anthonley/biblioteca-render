<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_activo'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mensaje' => 'Tu sesión expiró. Vuelve a iniciar sesión.']);
    exit();
}

require_once 'conexion.php';
require_once 'includes/ids.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
    exit();
}

$idSocio  = trim($_POST['id_socio'] ?? '');
$cedula   = trim($_POST['so_cedula'] ?? '');
$nombre   = trim($_POST['so_nombre'] ?? '');
$apellido = trim($_POST['so_apellido'] ?? '');
$telefono = trim($_POST['so_telefono'] ?? '');
$correo   = trim($_POST['so_correo'] ?? '');

if ($cedula === '' || $nombre === '' || $apellido === '' || $correo === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Cédula, nombre, apellido y correo son obligatorios.']);
    exit();
}

// ---- Nombre / apellido: solo letras y espacios ----
$soloLetras = '/^[A-Za-zÀ-ÖØ-öø-ÿñÑ\s]+$/u';
if (!preg_match($soloLetras, $nombre)) {
    echo json_encode(['ok' => false, 'mensaje' => 'El nombre solo puede contener letras y espacios.']);
    exit();
}
if (!preg_match($soloLetras, $apellido)) {
    echo json_encode(['ok' => false, 'mensaje' => 'El apellido solo puede contener letras y espacios.']);
    exit();
}

function esCedulaEcuatorianaValida(string $cedula): bool
{
    $cedula = trim($cedula);

    if (!preg_match('/^\d{10}$/', $cedula)) {
        return false;
    }

    $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    $suma = 0;

    for ($i = 0; $i < 9; $i++) {
        $digito = (int)$cedula[$i];
        $producto = $digito * $coeficientes[$i];

        if ($producto >= 10) {
            $producto -= 9;
        }

        $suma += $producto;
    }

    $digitoVerificador = $suma % 10 === 0 ? 0 : 10 - ($suma % 10);

    return $digitoVerificador === (int)$cedula[9];
}

if (!esCedulaEcuatorianaValida($cedula)) {
    echo json_encode(['ok' => false, 'mensaje' => 'La cédula ingresada no es válida para Ecuador.']);
    exit();
}

if ($telefono !== '' && !preg_match('/^\d{7,15}$/', $telefono)) {
    echo json_encode(['ok' => false, 'mensaje' => 'El teléfono debe contener solo números y tener entre 7 y 15 dígitos.']);
    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'mensaje' => 'El correo ingresado no es válido.']);
    exit();
}

try {
    // La cédula no puede repetirse (la BD ya tiene UNIQUE, pero validamos
    // antes para dar un mensaje claro en vez de un error genérico de PDO)
    if ($idSocio === '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM socio WHERE so_cedula = ?");
        $stmt->execute([$cedula]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM socio WHERE so_cedula = ? AND id_socio <> ?");
        $stmt->execute([$cedula, $idSocio]);
    }

    if ((int)$stmt->fetchColumn() > 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'Ya existe un socio registrado con esa cédula.']);
        exit();
    }

    if ($idSocio === '') {
        $idSocio = generarSiguienteId($pdo, 'socio', 'id_socio', 'SO');
        $stmt = $pdo->prepare(
            "INSERT INTO socio (id_socio, so_cedula, so_nombre, so_apellido, so_telefono, so_correo) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$idSocio, $cedula, $nombre, $apellido, $telefono !== '' ? $telefono : null, $correo]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE socio SET so_cedula = ?, so_nombre = ?, so_apellido = ?, so_telefono = ?, so_correo = ? WHERE id_socio = ?"
        );
        $stmt->execute([$cedula, $nombre, $apellido, $telefono !== '' ? $telefono : null, $correo, $idSocio]);
    }

    echo json_encode(['ok' => true]);
    exit();
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'mensaje' => 'Ya existe un socio registrado con esa cédula.']);
    exit();
}
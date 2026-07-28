<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInput = trim($_POST['usuario'] ?? '');
    $passInput    = $_POST['contrasena'] ?? '';

    if ($usuarioInput === '' || $passInput === '') {
        header('Location: ../index.php?error=1');
        exit();
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id_usuario, us_usuario, us_password_hash, us_rol
            FROM usuario
            WHERE us_usuario = ? AND us_estado = 1"
        );
        $stmt->execute([$usuarioInput]);
        $usuarioBD = $stmt->fetch();

        // Nota: la BD actual guarda la contraseña en texto plano ('1234'
        // en el seed) para simplificar las pruebas locales. Antes de subir
        // el proyecto se debe migrar a password_hash()/password_verify().
        if ($usuarioBD && $passInput === $usuarioBD['us_password_hash']) {
            $_SESSION['usuario_activo'] = [
                'id'     => $usuarioBD['id_usuario'],
                'nombre' => $usuarioBD['us_usuario'],
                'rol'    => $usuarioBD['us_rol'],
            ];
            header('Location: ../dashboard.php');
            exit();
        }

        header('Location: ../index.php?error=1');
        exit();
    } catch (PDOException $e) {
        die("Error en la base de datos: " . $e->getMessage());
    }
} else {
    header('Location: ../index.php');
    exit();
}
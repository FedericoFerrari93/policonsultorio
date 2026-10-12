<?php
require_once __DIR__ . '/../controllers/AuthController.php';

// Validar que la petición venga estrictamente de un formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitizar datos básicos de entrada
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header('Location: login.php?error=' . urlencode('Por favor, complete todos los campos.'));
        exit();
    }

    // Instanciar el controlador de autenticación
    $auth = new AuthController();
    $resultado = $auth->login($username, $password);

    if ($resultado['status'] === true) {
        // Redirección inteligente basada en el rol del usuario autenticado
        switch ($resultado['rol']) {
            case 'Administrador':
                header('Location: auditoria.php');
                break;
            case 'Recepcionista':
                header('Location: recepcion.php');
                break;
            case 'Médico':
                header('Location: medico.php');
                break;
            default:
                // Si tiene un rol inesperado, destruimos la sesión por seguridad
                if (session_status() === PHP_SESSION_NONE) session_start();
                session_destroy();
                header('Location: login.php?error=' . urlencode('Rol no reconocido en el sistema.'));
                break;
        }
        exit();
    } else {
        // Si las credenciales fallaron (o el usuario está inactivo), volvemos con el mensaje
        header('Location: login.php?error=' . urlencode($resultado['message']));
        exit();
    }

} else {
    // Si intentan entrar a este archivo escribiendo la URL directa, los rebotamos al login
    header('Location: login.php');
    exit();
}

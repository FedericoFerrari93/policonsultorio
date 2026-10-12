<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: views/login.php');
    exit();
}

switch ($_SESSION['rol'] ?? '') {
    case 'Administrador':
        header('Location: views/auditoria.php');
        break;
    case 'Recepcionista':
        header('Location: views/recepcion.php');
        break;
    case 'Médico':
        header('Location: views/medico.php');
        break;
    default:
        $_SESSION = [];
        session_destroy();
        header('Location: views/login.php?error=' . urlencode('Rol no reconocido en el sistema.'));
        break;
}
exit();
<?php
class AuthMiddleware {
    public static function verificarAcceso($rolesPermitidos = []) {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Si no ha iniciado sesión, redirigir al login
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: login.php');
            exit();
        }

        // Si su rol no está en la lista de permitidos, denegar acceso
        if (!empty($rolesPermitidos) && !in_array($_SESSION['rol'], $rolesPermitidos)) {
            // Aquí podrías registrar un log de 'ACCESO_DENEGADO' si lo deseas
            die("Error 403: No tienes permisos para acceder a este módulo.");
        }
    }
}

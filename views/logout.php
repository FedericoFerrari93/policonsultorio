<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Si había un usuario autenticado, registramos su salida en la auditoría antes de borrar todo
if (isset($_SESSION['usuario_id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $queryLog = "INSERT INTO logs_acceso (id_usuario, username_intentado, evento, ip_origen, user_agent) 
                     VALUES (:id_usuario, :username, 'LOGOUT', :ip, :user_agent)";
                     
        $stmt = $db->prepare($queryLog);
        $stmt->bindParam(':id_usuario', $_SESSION['usuario_id']);
        $stmt->bindParam(':username', $_SESSION['username']);
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':user_agent', $user_agent);
        
        $stmt->execute();
    } catch (Exception $e) {
        // Falla silenciosa si la BD no responde en el logout, para no trabar al usuario
    }
}

// Vaciar y destruir la sesión de PHP por completo
$_SESSION = array();
session_destroy();

// Redirigir a la pantalla de login
header('Location: login.php');
exit();

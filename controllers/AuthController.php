<?php
require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function login($username, $password) {
        // 1. Buscar al usuario y traer el nombre de su rol
        $query = "SELECT u.*, r.nombre_rol 
                  FROM usuarios u 
                  JOIN roles r ON u.id_rol = r.id_rol 
                  WHERE u.username = :username AND u.activo = 1 
                  LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        // 2. Verificar si el usuario existe y la contraseña es correcta
        if ($user && password_verify($password, $user->password_hash)) {
            // Iniciar sesión en PHP
            if (session_status() === PHP_SESSION_NONE) session_start();
            
            $_SESSION['usuario_id'] = $user->id_usuario;
            $_SESSION['username'] = $user->username;
            $_SESSION['rol'] = $user->nombre_rol;

            // Registrar Log Exitoso
            $this->registrarLog($user->id_usuario, $username, 'LOGIN_EXITOSO', $ip, $user_agent);
            return ['status' => true, 'rol' => $user->nombre_rol];
        } else {
            // Registrar Log Fallido (si el usuario existe mandamos su id, si no, NULL)
            $id_usuario = $user ? $user->id_usuario : null;
            $this->registrarLog($id_usuario, $username, 'LOGIN_FALLIDO', $ip, $user_agent);
            return ['status' => false, 'message' => 'Credenciales incorrectas.'];
        }
    }

    private function registrarLog($id_usuario, $username, $evento, $ip, $user_agent) {
        $query = "INSERT INTO logs_acceso (id_usuario, username_intentado, evento, ip_origen, user_agent) 
                  VALUES (:id_usuario, :username, :evento, :ip, :user_agent)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':evento', $evento);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':user_agent', $user_agent);
        $stmt->execute();
    }
}

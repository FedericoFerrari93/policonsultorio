<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/TurnoController.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección de ruta a nivel de backend
    AuthMiddleware::verificarAcceso(['Recepcionista', 'Administrador']);

    // Capturar y parsear datos de entrada
    $id_paciente     = (int)$_POST['id_paciente'];
    $id_medico       = (int)$_POST['id_medico'];
    $fecha_hora      = $_POST['fecha_hora'];
    $tipo_cobertura  = $_POST['tipo_cobertura'];
    $motivo_consulta = trim($_POST['motivo_consulta']);

    if (empty($id_paciente) || empty($id_medico) || empty($fecha_hora)) {
        header('Location: recepcion.php?status=error&msg=' . urlencode('Faltan campos obligatorios para agendar el turno.'));
        exit();
    }

    // Formatear fecha del HTML local (YYYY-MM-DDTHH:MM) al formato correcto de SQL (YYYY-MM-DD HH:MM:SS)
    $fecha_hora_sql = date('Y-m-d H:i:s', strtotime($fecha_hora));

    // Instanciar controlador de turnos y mandar los parámetros
    $turnoController = new TurnoController();
    $resultado = $turnoController->agendarTurno($id_paciente, $id_medico, $fecha_hora_sql, $tipo_cobertura, $motivo_consulta);

    if ($resultado['status'] === true) {
        header('Location: recepcion.php?status=success&msg=' . urlencode($resultado['message']));
    } else {
        header('Location: recepcion.php?status=error&msg=' . urlencode($resultado['message']));
    }
    exit();

} else {
    header('Location: login.php');
    exit();
}
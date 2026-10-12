<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

// Validar que la petición sea POST y que el usuario sea Médico
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthMiddleware::verificarAcceso(['Médico']);

    $id_turno = (int)$_POST['id_turno'];
    $id_paciente = (int)$_POST['id_paciente'];
    $id_medico = (int)$_POST['id_medico'];
    $diagnostico = trim($_POST['diagnostico']);
    $tratamiento = trim($_POST['tratamiento']);

    if (empty($diagnostico)) {
        die("Error: El campo diagnóstico es totalmente obligatorio.");
    }

    $database = new Database();
    $db = $database->getConnection();

    try {
        // Iniciamos transacción para asegurar que se hagan ambas cosas o ninguna
        $db->beginTransaction();

        // 1. Insertar la consulta en la Historia Clínica
        $queryHC = "INSERT INTO historias_clinicas (id_paciente, id_medico, id_turno, diagnostico, tratamiento) 
                    VALUES (:id_paciente, :id_medico, :id_turno, :diagnostico, :tratamiento)";
        $stmtHC = $db->prepare($queryHC);
        $stmtHC->bindParam(':id_paciente', $id_paciente);
        $stmtHC->bindParam(':id_medico', $id_medico);
        $stmtHC->bindParam(':id_turno', $id_turno);
        $stmtHC->bindParam(':diagnostico', $diagnostico);
        $stmtHC->bindParam(':tratamiento', $tratamiento);
        $stmtHC->execute();

        // 2. Modificar el estado del turno para quitarlo de pendientes
        $queryT = "UPDATE turnos SET estado = 'Atendido' WHERE id_turno = :id_turno";
        $stmtT = $db->prepare($queryT);
        $stmtT->bindParam(':id_turno', $id_turno);
        $stmtT->execute();

        // Confirmar cambios
        $db->commit();

        // Redirigir de vuelta al panel limpio
        header('Location: medico.php');
        exit();

    } catch (Exception $e) {
        // Deshacer si algo se rompió
        $db->rollBack();
        die("Error crítico al procesar la atención médica: " . $e->getMessage());
    }
} else {
    header('Location: login.php');
    exit();
}
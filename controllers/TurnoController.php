<?php
require_once __DIR__ . '/../config/database.php';

class TurnoController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function agendarTurno($id_paciente, $id_medico, $fecha_hora, $tipo_cobertura, $motivo) {
        try {
            // Iniciar transacción para asegurar consistencia
            $this->db->beginTransaction();

            // 1. Verificar si el médico ya está ocupado a esa hora
            $checkQuery = "SELECT id_turno FROM turnos WHERE id_medico = :id_medico AND fecha_hora = :fecha_hora LIMIT 1";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':id_medico', $id_medico);
            $checkStmt->bindParam(':fecha_hora', $fecha_hora);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                $this->db->rollBack();
                return ['status' => false, 'message' => 'El médico ya cuenta con un turno asignado para esta fecha y hora.'];
            }

            // 2. Insertar el turno si el horario está libre
            $insertQuery = "INSERT INTO turnos (id_paciente, id_medico, fecha_hora, tipo_cobertura, motivo_consulta, estado) 
                            VALUES (:id_paciente, :id_medico, :fecha_hora, :tipo_cobertura, :motivo, 'Pendiente')";
            
            $stmt = $this->db->prepare($insertQuery);
            $stmt->bindParam(':id_paciente', $id_paciente);
            $stmt->bindParam(':id_medico', $id_medico);
            $stmt->bindParam(':fecha_hora', $fecha_hora);
            $stmt->bindParam(':tipo_cobertura', $tipo_cobertura);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->execute();

            $this->db->commit();
            return ['status' => true, 'message' => 'Turno agendado con éxito.'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }
}

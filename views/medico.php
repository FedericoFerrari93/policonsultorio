<?php
// Evitamos duplicar el primer bloque de validación innecesario que tenías antes del HTML
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

// 1. Forzar seguridad: solo el rol 'Médico' entra aquí
AuthMiddleware::verificarAcceso(['Médico']);

$database = new Database();
$db = $database->getConnection();

// 2. Obtener el ID del médico vinculado al usuario en sesión
$id_usuario = $_SESSION['usuario_id'];
$queryMedico = "SELECT id_medico, nombre, apellido FROM medicos WHERE id_usuario = :id_usuario LIMIT 1";
$stmtM = $db->prepare($queryMedico);
$stmtM->bindParam(':id_usuario', $id_usuario);
$stmtM->execute();
$medico = $stmtM->fetch();

if (!$medico) {
    die("Error: Tu usuario no tiene un perfil médico asociado.");
}

$id_medico = $medico['id_medico'];

// 3. Traer los turnos del día de HOY para este médico
$hoy = date('Y-m-d');
$queryTurnos = "SELECT t.id_turno, t.fecha_hora, t.estado, p.id_paciente, p.nombre, p.apellido, p.dni 
                FROM turnos t 
                JOIN pacientes p ON t.id_paciente = p.id_paciente 
                WHERE t.id_medico = :id_medico AND DATE(t.fecha_hora) = :hoy 
                ORDER BY t.fecha_hora ASC";
$stmtT = $db->prepare($queryTurnos);
$stmtT->bindParam(':id_medico', $id_medico);
$stmtT->bindParam(':hoy', $hoy);
$stmtT->execute();
$turnos = $stmtT->fetchAll();

// 4. Capturar si el médico hizo clic para atender a un paciente específico
$paciente_seleccionado = null;
$historial_clinico = [];
$turno_actual_id = isset($_GET['atender_turno']) ? (int)$_GET['atender_turno'] : null;

if ($turno_actual_id) {
    // Buscar datos del paciente del turno seleccionado
    $queryP = "SELECT p.* FROM turnos t JOIN pacientes p ON t.id_paciente = p.id_paciente WHERE t.id_turno = :id_turno LIMIT 1";
    $stmtP = $db->prepare($queryP);
    $stmtP->bindParam(':id_turno', $turno_actual_id);
    $stmtP->execute();
    $paciente_seleccionado = $stmtP->fetch();

    if ($paciente_seleccionado) {
        // Traer sus antecedentes médicos anteriores ordenados por fecha
        $queryH = "SELECT h.fecha_atencion, h.diagnostico, h.tratamiento, m.apellido AS medico_apellido, e.nombre_especialidad 
                   FROM historias_clinicas h 
                   JOIN medicos m ON h.id_medico = m.id_medico 
                   JOIN turnos t ON h.id_turno = t.id_turno 
                   JOIN medico_especialidades me ON m.id_medico = me.id_medico 
                   JOIN especialidades e ON me.id_especialidad = e.id_especialidad 
                   WHERE h.id_paciente = :id_paciente 
                   GROUP BY h.id_historia 
                   ORDER BY h.fecha_atencion DESC";
        $stmtH = $db->prepare($queryH);
        $stmtH->bindParam(':id_paciente', $paciente_seleccionado['id_paciente']);
        $stmtH->execute();
        $historial_clinico = $stmtH->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultorio - Dr. <?php echo htmlspecialchars($medico['apellido']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">

    <!-- Barra Superior -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center border-b border-slate-100">
        <div class="flex items-center gap-3">
            <span class="text-xl font-bold text-slate-800">Layout Medilink Clínicas</span>
            <span class="bg-blue-50 text-blue-700 text-xs px-2.5 py-1 rounded-full font-medium">Panel Médico</span>
        </div>
        <div class="flex items-center gap-4">
            <p class="text-sm text-slate-600 font-medium">Dr. <?php echo htmlspecialchars($medico['nombre'] . " " . $medico['apellido']); ?></p>
            <a href="logout.php" class="text-xs bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded-lg transition font-semibold">Cerrar Sesión</a>
        </div>
    </header>

    <!-- Contenedor Principal (Doble Panel) -->
    <main class="flex-1 grid grid-cols-12 gap-6 p-6">
        
        <!-- PANEL IZQUIERDO: Agenda de Turnos del Día -->
        <section class="col-span-12 lg:col-span-4 bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col h-[calc(100vh-120px)]">
            <h2 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">
                📅 Pacientes de Hoy <span class="bg-slate-100 text-slate-700 text-xs px-2 py-0.5 rounded-full font-normal"><?php echo count($turnos); ?></span>
            </h2>
            <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                <?php if (empty($turnos)): ?>
                    <p class="text-sm text-slate-400 text-center py-8">No tienes turnos agendados para hoy.</p>
                <?php else: ?>
                    <?php foreach ($turnos as $t): 
                        $hora = date('H:i', strtotime($t['fecha_hora']));
                        $es_activo = ($t['id_turno'] == $turno_actual_id);
                    ?>
                        <div class="p-3 rounded-xl border transition flex items-center justify-between <?php echo $es_activo ? 'bg-blue-50/70 border-blue-200' : 'bg-white border-slate-100 hover:border-slate-200'; ?>">
                            <div>
                                <span class="text-xs font-bold px-2 py-0.5 rounded <?php echo $t['estado'] == 'Atendido' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'; ?>">
                                    <?php echo $hora; ?> - <?php echo htmlspecialchars($t['estado']); ?>
                                </span>
                                <h3 class="font-semibold text-slate-800 text-sm mt-1.5"><?php echo htmlspecialchars($t['apellido'] . ", " . $t['nombre']); ?></h3>
                                <p class="text-xs text-slate-400">DNI: <?php echo htmlspecialchars($t['dni']); ?></p>
                            </div>
                            <?php if ($t['estado'] != 'Atendido'): ?>
                                <a href="medico.php?atender_turno=<?php echo $t['id_turno']; ?>" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg shadow-sm font-medium transition">
                                    Atender
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- PANEL DERECHO: Box de Atención Médica -->
        <section class="col-span-12 lg:col-span-8 bg-white p-6 rounded-xl shadow-sm border border-slate-100 h-[calc(100vh-120px)] overflow-y-auto">
            <?php if (!$paciente_seleccionado): ?>
                <div class="h-full flex flex-col items-center justify-center text-slate-400 text-center">
                    <span class="text-4xl mb-2">📋</span>
                    <p class="font-medium">Ningún paciente seleccionado</p>
                    <p class="text-xs max-w-xs mt-1">Elige un paciente de la lista izquierda para iniciar la consulta, revisar sus antecedentes y cargar su diagnóstico.</p>
                </div>
            <?php else: ?>
                <!-- Cabecera de la Ficha Médica Activa -->
                <div class="border-b border-slate-100 pb-4 mb-6 flex justify-between items-start">
                    <div>
                        <span class="text-xs font-semibold text-blue-600 tracking-wider uppercase">Consulta Activa</span>
                        <h2 class="text-xl font-bold text-slate-800 mt-0.5"><?php echo htmlspecialchars($paciente_seleccionado['apellido'] . ", " . $paciente_seleccionado['nombre']); ?></h2>
                        <p class="text-xs text-slate-500 mt-1">
                            <strong>DNI:</strong> <?php echo htmlspecialchars($paciente_seleccionado['dni']); ?> | 
                            <strong>Edad:</strong> <?php echo date_diff(date_create($paciente_seleccionado['fecha_nacimiento']), date_create('today'))->y; ?> años
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Columna: Antecedentes Clínicos -->
                    <div class="space-y-4">
                        <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Historial Clínico</h3>
                        <div class="space-y-4 max-h-[350px] overflow-y-auto pr-1">
                            <?php if (empty($historial_clinico)): ?>
                                <p class="text-xs text-slate-400 italic">El paciente no registra consultas previas en el sistema.</p>
                            <?php else: ?>
                                <?php foreach ($historial_clinico as $h): ?>
                                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 text-xs">
                                        <div class="flex justify-between font-semibold text-slate-500 mb-1">
                                            <span>📅 <?php echo date('d/m/Y', strtotime($h['fecha_atencion'])); ?></span>
                                            <span>Dr/a. <?php echo htmlspecialchars($h['medico_apellido']); ?> (<?php echo htmlspecialchars($h['nombre_especialidad']); ?>)</span>
                                        </div>
                                        <p class="text-slate-700 font-medium mt-1"><strong>Diagnóstico:</strong> <?php echo nl2br(htmlspecialchars($h['diagnostico'])); ?></p>
                                        <?php if (!empty($h['tratamiento'])): ?>
                                            <p class="mt-2 text-slate-600"><strong>Tratamiento:</strong> <?php echo nl2br(htmlspecialchars($h['tratamiento'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form action="procesar_atencion.php" method="POST" class="space-y-4">
                        <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Registrar atención</h3>
                        <input type="hidden" name="id_turno" value="<?php echo (int)$turno_actual_id; ?>">
                        <input type="hidden" name="id_paciente" value="<?php echo (int)$paciente_seleccionado['id_paciente']; ?>">
                        <input type="hidden" name="id_medico" value="<?php echo (int)$id_medico; ?>">
                        <div>
                            <label for="diagnostico" class="mb-1 block text-sm font-medium text-slate-700">Diagnóstico</label>
                            <textarea id="diagnostico" name="diagnostico" rows="5" required
                                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                      placeholder="Describe el diagnóstico de la consulta"></textarea>
                        </div>
                        <div>
                            <label for="tratamiento" class="mb-1 block text-sm font-medium text-slate-700">Tratamiento e indicaciones</label>
                            <textarea id="tratamiento" name="tratamiento" rows="5"
                                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                      placeholder="Indica el tratamiento o las recomendaciones"></textarea>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Guardar atención
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

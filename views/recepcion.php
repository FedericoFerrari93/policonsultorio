<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

// 1. Validar seguridad: Solo Recepcionistas o Administradores pueden gestionar agendas
AuthMiddleware::verificarAcceso(['Recepcionista', 'Administrador']);

$database = new Database();
$db = $database->getConnection();

// 2. Cargar catálogos dinámicos para los menús desplegables del formulario
// Obtener Médicos
$queryMedicos = "SELECT id_medico, nombre, apellido FROM medicos ORDER BY apellido ASC";
$stmtM = $db->prepare($queryMedicos);
$stmtM->execute();
$medicos = $stmtM->fetchAll();

// Obtener Especialidades
$queryEsp = "SELECT id_especialidad, nombre_especialidad FROM especialidades ORDER BY nombre_especialidad ASC";
$stmtE = $db->prepare($queryEsp);
$stmtE->execute();
$especialidades = $stmtE->fetchAll();

// Obtener Obras Sociales activas
$queryOS = "SELECT id_obra_social, nombre_institucion FROM obras_sociales WHERE activo = 1 ORDER BY nombre_institucion ASC";
$stmtO = $db->prepare($queryOS);
$stmtO->execute();
$obras_sociales = $stmtO->fetchAll();

// 3. Capturar mensajes de éxito o error que vengan del controlador/procesador
$status = isset($_GET['status']) ? $_GET['status'] : null;
$message = isset($_GET['msg']) ? $_GET['msg'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepción - Gestión de Turnos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">

    <!-- Barra Superior -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center border-b border-slate-100">
        <div class="flex items-center gap-3">
            <span class="text-xl font-bold text-slate-800">🩺 Medilink Clínicas</span>
            <span class="bg-emerald-50 text-emerald-700 text-xs px-2.5 py-1 rounded-full font-medium">Admisión y Recepción</span>
        </div>
        <div class="flex items-center gap-4">
            <p class="text-sm text-slate-600 font-medium">Operador: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <a href="logout.php" class="text-xs bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded-lg transition font-semibold">Cerrar Sesión</a>
        </div>
    </header>

    <!-- Contenedor de Alertas Dinámicas -->
    <?php if ($message): ?>
        <div class="mx-6 mt-4 p-4 rounded-xl border <?php echo $status === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'; ?>">
            <div class="flex items-center gap-2 text-sm font-semibold">
                <span><?php echo $status === 'success' ? '✅' : '⚠️'; ?></span>
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contenedor Principal en Dos Columnas -->
    <main class="flex-1 grid grid-cols-12 gap-6 p-6">
        
        <!-- COLUMNA IZQUIERDA: Formulario de Paciente (Nuevo o Existente) -->
        <section class="col-span-12 lg:col-span-5 space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">👤 1. Identificación del Paciente</h2>
                
                <!-- Buscador de DNI simulado (En producción puede conectar a un endpoint de AJAX) -->
                <form action="procesar_paciente.php" method="POST" class="space-y-4">
                    <div>
                        <label for="dni" class="block text-xs font-semibold text-slate-600 mb-1">Documento Único de Identidad (DNI)</label>
                        <div class="flex gap-2">
                            <input type="text" name="dni" id="dni" required
                                   class="flex-1 text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:outline-none" 
                                   placeholder="Ej: 40123456">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="nombre" class="block text-xs font-semibold text-slate-600 mb-1">Nombre</label>
                            <input type="text" name="nombre" id="nombre" required
                                   class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label for="apellido" class="block text-xs font-semibold text-slate-600 mb-1">Apellido</label>
                            <input type="text" name="apellido" id="apellido" required
                                   class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="fecha_nacimiento" class="block text-xs font-semibold text-slate-600 mb-1">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required
                                   class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label for="telefono" class="block text-xs font-semibold text-slate-600 mb-1">Teléfono</label>
                            <input type="text" name="telefono" id="telefono"
                                   class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Ej: 1123456789">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-600 mb-1">Correo Electrónico</label>
                        <input type="email" name="email" id="email" required
                               class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="ejemplo@correo.com">
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-3 mt-2">
                        <div>
                            <label for="id_obra_social" class="block text-xs font-semibold text-slate-600 mb-1">Obra Social / Seguro</label>
                            <select name="id_obra_social" id="id_obra_social"
                                    class="w-full text-sm px-2 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="">Ninguna (Particular)</option>
                                <?php foreach ($obras_sociales as $os): ?>
                                    <option value="<?php echo $os['id_obra_social']; ?>"><?php echo htmlspecialchars($os['nombre_institucion']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="nro_afiliado" class="block text-xs font-semibold text-slate-600 mb-1">Nº de Afiliado</label>
                            <input type="text" name="nro_afiliado" id="nro_afiliado"
                                   class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Ej: 1-45612-3">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold py-2.5 px-4 rounded-lg shadow transition cursor-pointer">
                        👤 Guardar / Validar Datos Paciente
                    </button>
                </form>
            </div>
        </section>

        <!-- COLUMNA DERECHA: Formulario de Asignación de Turnos (7 Columnas) -->
        <section class="col-span-12 lg:col-span-7">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 h-full flex flex-col">
                <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">📅 2. Nueva Cita / Asignación de Turno</h2>
                
                <form action="procesar_turno_recepcion.php" method="POST" class="space-y-5 flex-1 flex flex-col justify-between">
                    <div class="space-y-4">
                        
                        <!-- Vincular el turno al ID del paciente que la recepción cargó previamente -->
                        <div>
                            <label for="id_paciente_turno" class="block text-xs font-semibold text-slate-600 mb-1">ID del Paciente Registrado</label>
                            <input type="number" name="id_paciente" id="id_paciente_turno" required
                                   class="w-1/3 text-sm px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-500 font-bold focus:outline-none"
                                   placeholder="Nº ID único">
                            <p class="text-[11px] text-slate-400 mt-1">Coloque el número de ID devuelto por el sistema tras registrar o verificar al paciente.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="id_medico" class="block text-xs font-semibold text-slate-600 mb-1">Médico Profesional</label>

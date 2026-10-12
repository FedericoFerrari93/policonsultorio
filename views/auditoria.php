<?php
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

// 1. Forzar seguridad: Solo el rol 'Administrador' puede auditar los logs del sistema
AuthMiddleware::verificarAcceso(['Administrador']);

$database = new Database();
$db = $database->getConnection();

// 2. Capturar filtros si es que el administrador seleccionó alguno
$filtro_evento = isset($_GET['evento']) ? trim($_GET['evento']) : '';

// 3. Construir la consulta SQL dinámica basada en el filtro
$queryLogs = "SELECT l.*, u.username AS usuario_registrado 
              FROM logs_acceso l
              LEFT JOIN usuarios u ON l.id_usuario = u.id_usuario";

if (!empty($filtro_evento)) {
    $queryLogs .= " WHERE l.evento = :evento";
}

$queryLogs .= " ORDER BY l.fecha_hora DESC LIMIT 100"; // Limitamos a los últimos 100 por rendimiento

$stmt = $db->prepare($queryLogs);

if (!empty($filtro_evento)) {
    $stmt->bindParam(':evento', $filtro_evento);
}

$stmt->execute();
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Auditoría y Seguridad</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">

    <!-- Barra Superior -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center border-b border-slate-100">
        <div class="flex items-center gap-3">
            <span class="text-xl font-bold text-slate-800">🛡️ Medilink Seguridad</span>
            <span class="bg-red-50 text-red-700 text-xs px-2.5 py-1 rounded-full font-medium">Consola de Administración</span>
        </div>
        <div class="flex items-center gap-4">
            <p class="text-sm text-slate-600 font-medium">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <a href="logout.php" class="text-xs bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded-lg transition font-semibold">Cerrar Sesión</a>
        </div>
    </header>

    <!-- Contenedor Principal -->
    <main class="flex-1 p-6 max-w-7xl w-full mx-auto space-y-6">
        
        <!-- Encabezado de la Sección y Filtros -->
        <section class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Registro de Logs y Accesos</h1>
                <p class="text-xs text-slate-500 mt-1">Monitoreo en tiempo real de inicios de sesión e intentos de vulneración al sistema.</p>
            </div>
            
            <!-- Formulario de Filtrado Rápido -->
            <form action="auditoria.php" method="GET" class="flex gap-2 w-full md:w-auto">
                <select name="evento" class="text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="">🔍 Mostrar todos los eventos</option>
                    <option value="LOGIN_EXITOSO" <?php echo $filtro_evento === 'LOGIN_EXITOSO' ? 'selected' : ''; ?>>🟢 Logins Exitosos</option>
                    <option value="LOGIN_FALLIDO" <?php echo $filtro_evento === 'LOGIN_FALLIDO' ? 'selected' : ''; ?>>🔴 Logins Fallidos</option>
                    <option value="LOGOUT" <?php echo $filtro_evento === 'LOGOUT' ? 'selected' : ''; ?>>🔵 Logouts</option>
                    <option value="ACCESO_DENEGADO" <?php echo $filtro_evento === 'ACCESO_DENEGADO' ? 'selected' : ''; ?>>⚠️ Accesos Denegados</option>
                </select>
                <button type="submit" class="text-xs bg-slate-800 hover:bg-slate-900 text-white font-semibold px-4 py-2 rounded-lg shadow transition cursor-pointer">
                    Filtrar
                </button>
            </form>
        </section>

        <!-- Tabla de Logs de Auditoría -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-4 px-6">Fecha / Hora</th>
                            <th class="py-4 px-6">Evento</th>
                            <th class="py-4 px-6">Usuario Intentado</th>
                            <th class="py-4 px-6">Dirección IP</th>
                            <th class="py-4 px-6">Dispositivo / User Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 italic">No se encontraron registros de auditoría que coincidan con el filtro.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): 
                                // Formatear fecha de forma legible
                                $fecha = date('d/m/Y H:i:s', strtotime($log['fecha_hora']));
                                
                                // Definir colores basados en el tipo de evento
                                $badge_class = 'bg-slate-50 text-slate-600';
                                if ($log['evento'] === 'LOGIN_EXITOSO') $badge_class = 'bg-emerald-50 text-emerald-700 font-bold';
                                if ($log['evento'] === 'LOGIN_FALLIDO') $badge_class = 'bg-red-50 text-red-700 font-bold animate-pulse';
                                if ($log['evento'] === 'ACCESO_DENEGADO') $badge_class = 'bg-amber-50 text-amber-700 font-bold';
                            ?>
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="py-4 px-6 font-medium text-slate-500 whitespace-nowrap"><?php echo $fecha; ?></td>
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] uppercase tracking-wide <?php echo $badge_class; ?>">
                                            <?php echo str_replace('_', ' ', $log['evento']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($log['username_intentado']); ?></div>
                                        <?php if ($log['id_usuario']): ?>
                                            <span class="text-[10px] text-slate-400">ID Usuario: <?php echo $log['id_usuario']; ?></span>
                                        <?php else: ?>
                                            <span class="text-[10px] text-red-400 italic">Cuenta no existente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 font-mono text-slate-600 whitespace-nowrap"><?php echo htmlspecialchars($log['ip_origen']); ?></td>
                                    <td class="py-4 px-6 max-w-xs truncate text-slate-400" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                        <?php echo htmlspecialchars($log['user_agent']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

</body>
</html>

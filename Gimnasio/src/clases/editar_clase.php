<?php
require_once 'class_functions.php';
require_once('../admin/admin_functions.php');
require_once('../includes/notificaciones_functions.php');
verificarAdmin();
$conn = obtenerConexion();

if (!isset($_SESSION['referer']) && isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['referer'] = $_SERVER['HTTP_REFERER'];
}


$id_clase = isset($_GET['id_clase']) ? intval($_GET['id_clase']) : null;

if (!$id_clase) {
    die("No se especificó una clase para editar.");
}

// Obtener datos de la clase
$stmt = $conn->prepare("SELECT * FROM clase WHERE id_clase = ?");
$stmt->bind_param('i', $id_clase);
$stmt->execute();
$clase = $stmt->get_result()->fetch_assoc();
if (!$clase) {
    die("Clase no encontrada.");
}
$stmt->close();

// Manejar la edición de la clase
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $id_monitor = intval($_POST['id_monitor']);
    $id_especialidad = intval($_POST['id_especialidad']);
    $fecha = $_POST['fecha'];
    $horario = $_POST['horario'];
    $duracion = intval($_POST['duracion']);
    $capacidad = intval($_POST['capacidad']);

    if (empty($nombre) || $id_monitor <= 0 || $id_especialidad <= 0 || empty($fecha) || empty($horario) || $duracion <= 0 || $capacidad <= 0) {
        $error = "Todos los campos son obligatorios y deben tener valores válidos.";
    } else {
        if ($duracion > 240) { // Validar duración máxima
            $error = "La duración no puede exceder las 4 horas (240 minutos).";
        } else {
            $fecha_hora_clase = strtotime("$fecha $horario");
            if ($fecha_hora_clase < time()) { // Validar que la fecha no esté en el pasado
                $error = "La fecha y el horario de la clase no pueden estar en el pasado.";
            } else {
                // Validar conflicto de horarios del monitor
                $stmt = $conn->prepare("
                    SELECT COUNT(*) AS total 
                    FROM clase 
                    WHERE id_monitor = ? 
                    AND fecha = ? 
                    AND NOT (
                        ADDTIME(horario, SEC_TO_TIME(duracion * 60 + 900)) <= ? 
                        OR ADDTIME(?, SEC_TO_TIME(? * 60 + 900)) <= horario
                    )
                    AND id_clase != ? AND id_monitor = ?

                ");
                $stmt->bind_param(
                    'isssiii',
                    $id_monitor,
                    $fecha,
                    $horario,
                    $horario,
                    $duracion,
                    $id_clase_param,
                    $id_monitor
                );

                $stmt->execute();
                $result = $stmt->get_result();
                $conflicto = $result->fetch_assoc()['total'];
                $stmt->close();

                if ($conflicto > 0) {
                    $error = "El monitor ya tiene una clase programada en este horario o dentro del margen de tiempo permitido.";
                } else {
                    // Obtener el monitor anterior antes de la actualización
                    $stmt = $conn->prepare("SELECT id_monitor FROM clase WHERE id_clase = ?");
                    $stmt->bind_param('i', $id_clase);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $monitorAnterior = $result->fetch_assoc()['id_monitor'] ?? null;
                    $stmt->close();

                    // Actualizar la clase
                    $stmt = $conn->prepare("
                        UPDATE clase SET 
                            nombre = ?, id_monitor = ?, id_especialidad = ?, fecha = ?, 
                            horario = ?, duracion = ?, capacidad_maxima = ?
                        WHERE id_clase = ?
                    ");
                    $stmt->bind_param('siissiii', $nombre, $id_monitor, $id_especialidad, $fecha, $horario, $duracion, $capacidad, $id_clase);
                    $stmt->execute();
                    $stmt->close();

                    // Notificar al monitor anterior si ha cambiado
                    if ($monitorAnterior && $monitorAnterior != $id_monitor) {
                        $stmt = $conn->prepare("SELECT u.id_usuario FROM monitor m INNER JOIN usuario u ON m.id_usuario = u.id_usuario WHERE m.id_monitor = ?");
                        $stmt->bind_param('i', $monitorAnterior);
                        $stmt->execute();
                        $monitorAnteriorUsuario = $stmt->get_result()->fetch_assoc()['id_usuario'] ?? null;
                        $stmt->close();

                        if ($monitorAnteriorUsuario) {
                            $mensajeAnterior = "Ya no estás asignado a la clase '{$nombre}'.";
                            enviarNotificacion($conn, $monitorAnteriorUsuario, $mensajeAnterior);
                        }
                    }

                    // Notificar al nuevo monitor
                    $stmt = $conn->prepare("SELECT u.id_usuario FROM monitor m INNER JOIN usuario u ON m.id_usuario = u.id_usuario WHERE m.id_monitor = ?");
                    $stmt->bind_param('i', $id_monitor);
                    $stmt->execute();
                    $nuevoMonitorUsuario = $stmt->get_result()->fetch_assoc()['id_usuario'] ?? null;
                    $stmt->close();

                    if ($nuevoMonitorUsuario) {
                        $mensajeNuevo = "Estás asignado la clase '{$nombre}'. Verifica los detalles.";
                        enviarNotificacion($conn, $nuevoMonitorUsuario, $mensajeNuevo);
                    }

                    // Notificar a los miembros inscritos en la clase
                    $stmt = $conn->prepare("
                        SELECT id_usuario 
                        FROM asistencia a 
                        INNER JOIN miembro m ON a.id_miembro = m.id_miembro 
                        WHERE id_clase = ?
                    ");
                    $stmt->bind_param('i', $id_clase);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($miembro = $result->fetch_assoc()) {
                        $mensajeMiembro = "La clase '{$nombre}' a la que estás inscrito ha sido modificada. Verifica los nuevos detalles.";
                        enviarNotificacion($conn, $miembro['id_usuario'], $mensajeMiembro);
                    }
                    $stmt->close();

                    $success = "Clase actualizada exitosamente.";
                }
            }
        }
    }
}


// Consulta para Monitores
$monitores = $conn->query("
    SELECT mo.id_monitor, u.nombre AS monitor_nombre, 
           GROUP_CONCAT(e.id_especialidad, ':', e.nombre SEPARATOR ',') AS especialidades
    FROM monitor mo
    JOIN usuario u ON mo.id_usuario = u.id_usuario
    LEFT JOIN monitor_especialidad me ON mo.id_monitor = me.id_monitor
    LEFT JOIN especialidad e ON me.id_especialidad = e.id_especialidad
    GROUP BY mo.id_monitor
");

// Consulta para Especialidades
$especialidades = $conn->query("
    SELECT e.id_especialidad, e.nombre AS especialidad_nombre, 
           GROUP_CONCAT(mo.id_monitor, ':', u.nombre, ':', mo.disponibilidad SEPARATOR ',') AS monitores
    FROM especialidad e
    LEFT JOIN monitor_especialidad me ON e.id_especialidad = me.id_especialidad
    LEFT JOIN monitor mo ON me.id_monitor = mo.id_monitor
    LEFT JOIN usuario u ON mo.id_usuario = u.id_usuario
    GROUP BY e.id_especialidad
");

$title = "Editar Clase";
include '../admin/admin_header.php';
?>

<body>
    <main>
        <h1>Editar Clase</h1>

        <!-- Mensajes de confirmación o error -->
        <?php if (isset($success)): ?>
            <div id="mensaje-flotante" class="mensaje-confirmacion">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php elseif (isset($error)): ?>
            <div id="mensaje-flotante" class="mensaje-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>


        <!-- Formulario para editar los detalles de la clase -->
        <section class="form_container">
            <form method="POST">
                <!-- Información básica de la clase -->
                <h2>Información de la Clase</h2>

                <label for="nombre">Nombre de la Clase:</label>
                <input type="text" id="nombre" name="nombre"
                    value="<?= htmlspecialchars($_POST['nombre'] ?? $clase['nombre'] ?? '') ?>"
                    required title="Introduce un nombre descriptivo para la clase.">

                <!-- Selección de especialidad -->
                <h2>Especialidad de la Clase</h2>
                <label for="id_especialidad">Especialidad:</label>
                <select id="id_especialidad" name="id_especialidad" required title="Selecciona la especialidad a la que pertenece la clase.">
                    <option value="" selected disabled>Seleccionar especialidad</option>
                    <?php while ($especialidad = $especialidades->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($especialidad['id_especialidad']) ?>"
                            data-monitores="<?= htmlspecialchars($especialidad['monitores']) ?>"
                            <?= (($_POST['id_especialidad'] ?? $clase['id_especialidad'] ?? '') == $especialidad['id_especialidad']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($especialidad['especialidad_nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Selección de monitor -->
                <h2>Asignación de Monitor</h2>
                <label for="id_monitor">Monitor Responsable:</label>
                <select id="id_monitor" name="id_monitor" data-selected-monitor="<?= htmlspecialchars($clase['id_monitor']) ?>"
                    title="Selecciona el monitor que estará a cargo de la clase.">
                    <option value="" disabled>Seleccionar monitor</option>
                    <?php while ($monitor = $monitores->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($monitor['id_monitor']) ?>"
                            <?= ($clase['id_monitor'] == $monitor['id_monitor']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($monitor['monitor_nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Fecha y horario de la clase -->
                <h2>Programación de la Clase</h2>
                <label for="fecha">Fecha de la Clase:</label>
                <input type="date" id="fecha" name="fecha"
                    value="<?= htmlspecialchars($_POST['fecha'] ?? $clase['fecha'] ?? '') ?>"
                    required title="Selecciona la fecha en la que se impartirá la clase.">

                <label for="horario">Horario de Inicio:</label>
                <input type="time" id="horario" name="horario"
                    value="<?= htmlspecialchars($_POST['horario'] ?? $clase['horario'] ?? '') ?>"
                    required title="Selecciona la hora de inicio de la clase.">

                <!-- Duración y capacidad -->
                <h2>Duración y Capacidad</h2>
                <label for="duracion">Duración (minutos):</label>
                <input type="number" id="duracion" name="duracion"
                    value="<?= htmlspecialchars($_POST['duracion'] ?? $clase['duracion'] ?? '') ?>"
                    required title="Introduce la duración total de la clase en minutos.">

                <label for="capacidad">Capacidad Máxima de Alumnos:</label>
                <input type="number" id="capacidad" name="capacidad"
                    value="<?= htmlspecialchars($_POST['capacidad'] ?? $clase['capacidad_maxima'] ?? '') ?>"
                    required title="Introduce el número máximo de alumnos que pueden inscribirse en la clase.">

                <!-- Botones de acción -->
                <div class="button-container">
                    <button type="submit" class="btn-general" title="Guarda los cambios realizados en la clase.">Actualizar Clase</button>
                    <button onclick="window.history.back()" class="btn-general btn-secondary">Volver</button>
                </div>
            </form>
        </section>
    </main>

    <!-- Archivos JavaScript -->
    <script src="../../assets/js/dinamica_especialidades.js"></script>
    <script src="../../assets/js/validacion_clase.js"></script>
    <script src="../assets/js/validacion.js"></script>
    <script>
        configurarMonitoresPorEspecialidad('id_especialidad', 'id_monitor');
        configurarRestriccionesFechaHora('fecha', 'horario');
    </script>


</body>
<?php include '../includes/footer.php'; ?>
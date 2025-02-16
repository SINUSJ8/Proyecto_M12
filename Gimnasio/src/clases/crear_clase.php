<?php
require_once 'class_functions.php';
require_once('../admin/admin_functions.php');
verificarAdmin();
$conn = obtenerConexion();

if (!isset($_SESSION['referer']) && isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['referer'] = $_SERVER['HTTP_REFERER'];
}

$id_clase = isset($_GET['id_clase']) ? intval($_GET['id_clase']) : null;

if ($id_clase) {
    $stmt = $conn->prepare("SELECT * FROM clase WHERE id_clase = ?");
    $stmt->bind_param('i', $id_clase);
    $stmt->execute();
    $clase = $stmt->get_result()->fetch_assoc();
    if (!$clase) {
        die("Clase no encontrada.");
    }
    $stmt->close();
}

// Manejar el formulario de creación o edición
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $id_monitor = intval($_POST['id_monitor']);
    $id_especialidad = intval($_POST['id_especialidad']);
    $fecha = $_POST['fecha'];
    $horario = $_POST['horario'];
    $duracion = intval($_POST['duracion']);
    $capacidad = intval($_POST['capacidad']);

    // Validación de campos obligatorios
    if (empty($nombre) || $id_monitor <= 0 || $id_especialidad <= 0 || empty($fecha) || empty($horario) || $duracion <= 0 || $capacidad <= 0) {
        $error = "Todos los campos son obligatorios y deben tener valores válidos.";
    } else {
        // Validación de duración máxima
        if ($duracion > 240) { // Duración máxima de 4 horas (240 minutos)
            $error = "La duración no puede exceder las 4 horas (240 minutos).";
        } else {
            // Validación de fecha y horario (no en el pasado)
            $fecha_hora_clase = strtotime("$fecha $horario");
            $fecha_hora_actual = time();
            if ($fecha_hora_clase < $fecha_hora_actual) {
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
                    AND id_clase != ?
                ");

                $id_clase_param = $id_clase ?? 0;
                $stmt->bind_param(
                    'isssii',
                    $id_monitor,
                    $fecha,
                    $horario,
                    $horario,
                    $duracion,
                    $id_clase_param
                );

                $stmt->execute();
                $result = $stmt->get_result();
                $conflicto = $result->fetch_assoc()['total'];
                $stmt->close();

                // Comprobar si hay conflictos
                if ($conflicto > 0) {
                    $error = "El monitor ya tiene una clase programada en este horario o dentro del margen de tiempo permitido.";
                } else {
                    // Crear o actualizar clase
                    if ($id_clase) {
                        $stmt = $conn->prepare("
                            UPDATE clase SET 
                                nombre = ?, id_monitor = ?, id_especialidad = ?, fecha = ?, 
                                horario = ?, duracion = ?, capacidad_maxima = ?
                            WHERE id_clase = ?
                        ");
                        $stmt->bind_param('siissiii', $nombre, $id_monitor, $id_especialidad, $fecha, $horario, $duracion, $capacidad, $id_clase);
                        $stmt->execute();
                        $stmt->close();

                        // Redirigir con mensaje de éxito
                        header("Location: {$_SERVER['PHP_SELF']}?id_clase=$id_clase&mensaje=Clase actualizada exitosamente");
                        exit();
                    } else {
                        crearClase($conn, $nombre, $id_monitor, $id_especialidad, $fecha, $horario, $duracion, $capacidad);

                        // Redirigir con mensaje de éxito
                        header("Location: {$_SERVER['PHP_SELF']}?mensaje=Clase creada exitosamente");
                        exit();
                    }
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

$title = "Crear Nueva Clase";
include '../admin/admin_header.php';
?>

<body>
    <main>
        <h1>Crear Nueva Clase</h1>

        <!-- Mensajes de error o éxito -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div id="mensaje-flotante" class="mensaje-confirmacion">
                <?php echo htmlspecialchars($_GET['mensaje']); ?>
            </div>
        <?php elseif (isset($error)): ?>
            <div id="mensaje-flotante" class="mensaje-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear una nueva clase -->
        <section class="form_container">
            <form id="form_clase" method="POST">
                <input type="hidden" name="accion" value="crear_clase">

                <!-- Información básica de la clase -->
                <h2>Información General de la Clase</h2>

                <label for="nombre">Nombre de la Clase:</label>
                <input type="text" id="nombre" name="nombre"
                    value="<?= htmlspecialchars($clase['nombre'] ?? '') ?>"
                    required title="Introduce un nombre claro y descriptivo para la clase.">

                <!-- Selección de especialidad -->
                <h2>Especialidad de la Clase</h2>
                <label for="id_especialidad">Especialidad:</label>
                <select id="id_especialidad" name="id_especialidad" required
                    title="Selecciona la especialidad a la que pertenece esta clase.">
                    <option value="" selected disabled>Seleccionar especialidad</option>
                    <?php while ($especialidad = $especialidades->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($especialidad['id_especialidad']) ?>"
                            data-monitores="<?= htmlspecialchars($especialidad['monitores']) ?>"
                            <?= isset($clase['id_especialidad']) && $clase['id_especialidad'] == $especialidad['id_especialidad'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($especialidad['especialidad_nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Asignación de monitor -->
                <h2>Asignación del Monitor</h2>
                <label for="id_monitor">Monitor Responsable:</label>
                <select id="id_monitor" name="id_monitor"
                    <?= isset($clase['id_monitor']) ? "data-selected-monitor='" . htmlspecialchars($clase['id_monitor']) . "'" : ''; ?>
                    required title="Selecciona el monitor que dirigirá esta clase.">
                    <option value="" selected disabled>Seleccionar monitor</option>
                </select>

                <!-- Programación de la clase -->
                <h2>Horario y Fecha de la Clase</h2>
                <label for="fecha">Fecha de la Clase:</label>
                <input type="date" id="fecha" name="fecha"
                    value="<?= htmlspecialchars($clase['fecha'] ?? '') ?>"
                    required title="Selecciona la fecha en la que se impartirá la clase.">

                <label for="horario">Horario de Inicio:</label>
                <input type="time" id="horario" name="horario"
                    value="<?= htmlspecialchars($clase['horario'] ?? '') ?>"
                    required title="Indica la hora en la que comenzará la clase.">

                <!-- Duración y capacidad -->
                <h2>Duración y Capacidad de la Clase</h2>
                <label for="duracion">Duración (minutos):</label>
                <input type="number" id="duracion" name="duracion"
                    value="<?= htmlspecialchars($clase['duracion'] ?? '') ?>"
                    required title="Especifica la duración de la clase en minutos.">

                <label for="capacidad">Capacidad Máxima de Alumnos:</label>
                <input type="number" id="capacidad" name="capacidad"
                    value="<?= htmlspecialchars($clase['capacidad_maxima'] ?? '') ?>"
                    required title="Indica el número máximo de alumnos que pueden inscribirse en esta clase.">

                <!-- Botones de acción -->
                <div class="button-container">
                    <button type="submit" class="btn-general"
                        title="Guarda la clase y agrégala al sistema."><?= $id_clase ? 'Actualizar Clase' : 'Crear Clase'; ?>
                    </button>
                    <button onclick="window.history.back()" class="btn-general btn-secondary">Volver</button>
                </div>
            </form>
        </section>
    </main>

    <!-- Archivos JavaScript -->
    <script src="../../assets/js/dinamica_especialidades.js"></script>
    <script src="../../assets/js/validacion.js"></script>
    <script>
        configurarMonitoresPorEspecialidad('id_especialidad', 'id_monitor');
        configurarRestriccionesFechaHora('fecha', 'horario');
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
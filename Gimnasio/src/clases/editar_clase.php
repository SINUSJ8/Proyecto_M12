<?php
require_once 'class_functions.php';
require_once('../admin/admin_functions.php');
verificarAdmin();
$conn = obtenerConexion();

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
    // Inicializar variables del formulario
    $nombre = $_POST['nombre'];
    $id_monitor = intval($_POST['id_monitor']);
    $id_especialidad = intval($_POST['id_especialidad']);
    $fecha = $_POST['fecha'];
    $horario = $_POST['horario'];
    $duracion = intval($_POST['duracion']);
    $capacidad = intval($_POST['capacidad']);

    // Validar campos obligatorios
    if (empty($nombre) || empty($id_monitor) || empty($id_especialidad) || empty($fecha) || empty($horario) || empty($duracion) || empty($capacidad)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        // Obtener el monitor anterior
        $stmt = $conn->prepare("SELECT id_monitor FROM clase WHERE id_clase = ?");
        $stmt->bind_param('i', $id_clase);
        $stmt->execute();
        $result = $stmt->get_result();
        $claseActual = $result->fetch_assoc();
        $monitorAnterior = $claseActual['id_monitor'];
        $stmt->close();

        // Actualizar la clase
        $stmt = $conn->prepare("UPDATE clase SET nombre = ?, id_monitor = ?, id_especialidad = ?, fecha = ?, horario = ?, duracion = ?, capacidad_maxima = ? WHERE id_clase = ?");
        $stmt->bind_param('siissiii', $nombre, $id_monitor, $id_especialidad, $fecha, $horario, $duracion, $capacidad, $id_clase);
        $stmt->execute();
        $stmt->close();

        // Notificar al monitor anterior si ha cambiado
        if ($monitorAnterior != $id_monitor) {
            $stmt = $conn->prepare("SELECT u.id_usuario, u.nombre FROM monitor m INNER JOIN usuario u ON m.id_usuario = u.id_usuario WHERE m.id_monitor = ?");
            $stmt->bind_param('i', $monitorAnterior);
            $stmt->execute();
            $monitorAnteriorDatos = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($monitorAnteriorDatos) {
                $mensajeAnterior = "Ya no estás asignado a la clase '{$nombre}'.";
                enviarNotificacion($conn, $monitorAnteriorDatos['id_usuario'], $mensajeAnterior);
            }
        }

        // Notificar al nuevo monitor
        $monitor = obtenerMonitorDeClase($conn, $id_clase);
        if ($monitor) {
            $mensajeMonitor = "La clase '{$nombre}' que impartes ha sido modificada. Verifica los nuevos detalles.";
            enviarNotificacion($conn, $monitor['id_usuario'], $mensajeMonitor);
        }

        // Notificar a los miembros inscritos
        $miembros = obtenerMiembrosInscritos($conn, $id_clase);
        foreach ($miembros as $miembro) {
            $mensaje = "La clase '{$nombre}' ha sido modificada. Verifica los nuevos detalles.";
            enviarNotificacion($conn, $miembro['id_usuario'], $mensaje);
        }

        $success = "Clase actualizada exitosamente.";
    }
}


// Consulta para Monitores
$monitores = $conn->query(
    "SELECT mo.id_monitor, u.nombre AS monitor_nombre, 
           GROUP_CONCAT(e.id_especialidad, ':', e.nombre SEPARATOR ',') AS especialidades
    FROM monitor mo
    JOIN usuario u ON mo.id_usuario = u.id_usuario
    LEFT JOIN monitor_especialidad me ON mo.id_monitor = me.id_monitor
    LEFT JOIN especialidad e ON me.id_especialidad = e.id_especialidad
    GROUP BY mo.id_monitor"
);

// Consulta para Especialidades
$especialidades = $conn->query(
    "SELECT e.id_especialidad, e.nombre AS especialidad_nombre, 
           GROUP_CONCAT(mo.id_monitor, ':', u.nombre, ':', mo.disponibilidad SEPARATOR ',') AS monitores
    FROM especialidad e
    LEFT JOIN monitor_especialidad me ON e.id_especialidad = me.id_especialidad
    LEFT JOIN monitor mo ON me.id_monitor = mo.id_monitor
    LEFT JOIN usuario u ON mo.id_usuario = u.id_usuario
    GROUP BY e.id_especialidad"
);

$title = "Editar Clase";
include '../admin/admin_header.php';
?>

<body>
    <main>
        <h1>Editar Clase</h1>

        <!-- Mensajes de error o éxito -->
        <?php if (isset($success)): ?>
            <p class="mensaje-confirmacion"><?php echo htmlspecialchars($success); ?></p>
        <?php elseif (isset($error)): ?>
            <p class="mensaje-error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <!-- Formulario para editar clase -->
        <section class="form_container">
            <form method="POST">
                <label for="nombre">Nombre de la Clase:</label>
                <input type="text" id="nombre" name="nombre"
                    value="<?= htmlspecialchars($clase['nombre'] ?? '') ?>" required>

                <label for="id_especialidad">Especialidad:</label>
                <select id="id_especialidad" name="id_especialidad" required>
                    <option value="" selected disabled>Seleccionar especialidad</option>
                    <?php while ($especialidad = $especialidades->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($especialidad['id_especialidad']) ?>"
                            data-monitores="<?= htmlspecialchars($especialidad['monitores']) ?>"
                            <?= isset($clase['id_especialidad']) && $clase['id_especialidad'] == $especialidad['id_especialidad'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($especialidad['especialidad_nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="id_monitor">Monitor:</label>
                <select id="id_monitor" name="id_monitor"
                    data-selected-monitor="<?= htmlspecialchars($clase['id_monitor'] ?? '') ?>"
                    required>
                    <option value="" disabled>Seleccionar monitor</option>
                    <?php
                    $stmt = $conn->prepare(
                        "SELECT mo.id_monitor, u.nombre, mo.disponibilidad
                        FROM monitor_especialidad me
                        JOIN monitor mo ON me.id_monitor = mo.id_monitor
                        JOIN usuario u ON mo.id_usuario = u.id_usuario
                        WHERE me.id_especialidad = ?"
                    );
                    $stmt->bind_param('i', $clase['id_especialidad']);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($monitor = $result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($monitor['id_monitor']); ?>"
                            <?= $monitor['id_monitor'] == $clase['id_monitor'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($monitor['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                    <?php $stmt->close(); ?>
                </select>

                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha"
                    value="<?= htmlspecialchars($clase['fecha'] ?? '') ?>" required>

                <label for="horario">Horario:</label>
                <input type="time" id="horario" name="horario"
                    value="<?= htmlspecialchars($clase['horario'] ?? '') ?>" required>

                <label for="duracion">Duración (min):</label>
                <input type="number" id="duracion" name="duracion"
                    value="<?= htmlspecialchars($clase['duracion'] ?? '') ?>" required>

                <label for="capacidad">Capacidad Máxima:</label>
                <input type="number" id="capacidad" name="capacidad"
                    value="<?= htmlspecialchars($clase['capacidad_maxima'] ?? '') ?>" required>

                <button type="submit" class="button-container">Actualizar Clase</button>
            </form>
        </section>
    </main>
    <script src="../../assets/js/dinamica_especialidades.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            configurarMonitoresPorEspecialidad('id_especialidad', 'id_monitor');
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
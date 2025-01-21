<?php
require_once 'class_functions.php';
require_once('../admin/admin_functions.php');
verificarAdmin();
$conn = obtenerConexion();

if (!isset($_SESSION['referer'])) {
    $_SESSION['referer'] = $_SERVER['HTTP_REFERER'] ?? '../clases/clases.php';
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

    // Validar campos obligatorios
    if (empty($nombre) || $id_monitor <= 0 || $id_especialidad <= 0 || empty($fecha) || empty($horario) || $duracion <= 0 || $capacidad <= 0) {
        $error = "Todos los campos son obligatorios y deben tener valores válidos.";
    } else {
        // Validar duración máxima
        if ($duracion > 240) { // Duración máxima de 4 horas
            $error = "La duración no puede exceder las 4 horas (240 minutos).";
        } else {
            // Validar fecha y horario (no en el pasado)
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
                    AND (
                        horario < ADDTIME(?, SEC_TO_TIME(? * 60 + 900))
                        AND ADDTIME(horario, SEC_TO_TIME(duracion * 60 + 900)) > ?
                    )
                    AND id_clase != ?
                ");
                $stmt->bind_param(
                    'isssii',
                    $id_monitor,
                    $fecha,
                    $horario,
                    $duracion,
                    $horario,
                    $id_clase
                );
                $stmt->execute();
                $result = $stmt->get_result();
                $conflicto = $result->fetch_assoc()['total'];
                $stmt->close();

                if ($conflicto > 0) {
                    $error = "El monitor ya tiene una clase programada en este horario o dentro del margen de tiempo permitido.";
                } else {
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

                    $success = "Clase actualizada exitosamente.";
                    unset($_SESSION['referer']);
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
                    value="<?= htmlspecialchars($_POST['nombre'] ?? $clase['nombre'] ?? '') ?>" required>

                <label for="id_especialidad">Especialidad:</label>
                <select id="id_especialidad" name="id_especialidad" required>
                    <option value="" selected disabled>Seleccionar especialidad</option>
                    <?php while ($especialidad = $especialidades->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($especialidad['id_especialidad']) ?>"
                            data-monitores="<?= htmlspecialchars($especialidad['monitores']) ?>"
                            <?= (($_POST['id_especialidad'] ?? $clase['id_especialidad'] ?? '') == $especialidad['id_especialidad']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($especialidad['especialidad_nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="id_monitor">Monitor:</label>
                <select id="id_monitor" name="id_monitor" required>
                    <option value="" selected disabled>Seleccionar monitor</option>
                </select>

                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha"
                    value="<?= htmlspecialchars($_POST['fecha'] ?? $clase['fecha'] ?? '') ?>" required>

                <label for="horario">Horario:</label>
                <input type="time" id="horario" name="horario"
                    value="<?= htmlspecialchars($_POST['horario'] ?? $clase['horario'] ?? '') ?>" required>

                <label for="duracion">Duración (min):</label>
                <input type="number" id="duracion" name="duracion"
                    value="<?= htmlspecialchars($_POST['duracion'] ?? $clase['duracion'] ?? '') ?>" required>

                <label for="capacidad">Capacidad Máxima:</label>
                <input type="number" id="capacidad" name="capacidad"
                    value="<?= htmlspecialchars($_POST['capacidad'] ?? $clase['capacidad_maxima'] ?? '') ?>" required>

                <div class="button-container">
                    <button type="submit" class="btn-general">Actualizar Clase</button>
                    <a href="<?= htmlspecialchars($_SESSION['referer']) ?>" class="btn-general btn-secondary">Cancelar</a>

                </div>

            </form>
        </section>
    </main>

    <script src="../../assets/js/dinamica_especialidades.js"></script>
    <script src="../../assets/js/validacion_clase.js"></script>
    <script>
        configurarMonitoresPorEspecialidad('id_especialidad', 'id_monitor');
        configurarRestriccionesFechaHora('fecha', 'horario');
    </script>
    <?php include '../includes/footer.php'; ?>
</body>
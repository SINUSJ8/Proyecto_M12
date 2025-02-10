<?php
require_once('../monitores/monitor_functions.php');
require_once('../usuarios/user_functions.php');

verificarAdmin();

$conn = obtenerConexion();
$title = "Editar Monitor";
if (!isset($_SESSION['referer']) && isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['referer'] = $_SERVER['HTTP_REFERER'];
}

if (!isset($_GET['id_usuario'])) {
    die("ID de usuario no proporcionado.");
}

$id_usuario = $_GET['id_usuario'];
$monitor = obtenerMonitorPorID($conn, $id_usuario);

// Asegurarse de que el array 'entrenamientos' esté definido aunque esté vacío
$monitor['entrenamientos'] = isset($monitor['entrenamientos']) ? $monitor['entrenamientos'] : [];

// Obtener entrenamientos y especialidades disponibles para los desplegables
$entrenamientos = obtenerEntrenamientos($conn);
$especialidades = obtenerEspecialidades($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? null;
    $email = $_POST['email'] ?? null;
    $experiencia = $_POST['experiencia'] ?? null;
    $disponibilidad = $_POST['disponibilidad'] ?? null;
    $entrenamientos_seleccionados = $_POST['entrenamiento'] ?? [];

    if (!$nombre || !$email || $experiencia === null || $disponibilidad === null) {
        $mensaje = "Error: Todos los campos son obligatorios.";
        header("Location: edit_monitor.php?id_usuario=$id_usuario&mensaje=" . urlencode($mensaje));
        exit();
    }

    // Actualizar el monitor en la base de datos
    $resultado = actualizarMonitor($conn, $id_usuario, $nombre, $email, $monitor['especialidad'], $experiencia, $disponibilidad);

    if ($resultado['success']) {
        $id_monitor = $monitor['id_monitor'];

        if ($id_monitor) {
            actualizarEntrenamientosMonitor($conn, $id_monitor, $entrenamientos_seleccionados);
            $mensaje = "Monitor actualizado correctamente.";
        } else {
            $mensaje = "Error: Monitor no encontrado.";
        }

        // Volver a cargar los datos del monitor después de actualizar
        $monitor = obtenerMonitorPorID($conn, $id_usuario);
    } else {
        $mensaje = $resultado['message'];
    }
}

include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2 class="section-title">Editar Monitor</h2>

        <?php if (isset($mensaje)): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        title: "<?php echo strpos($mensaje, 'Error') === false ? '¡Éxito!' : '¡Error!'; ?>",
                        text: "<?php echo htmlspecialchars($mensaje); ?>",
                        icon: "<?php echo strpos($mensaje, 'Error') === false ? 'success' : 'error'; ?>",
                        confirmButtonText: "Aceptar"
                    });
                });
            </script>
        <?php endif; ?>


        <div class="form_container">
            <?php if ($monitor): ?>
                <form method="POST" action="edit_monitor.php?id_usuario=<?php echo htmlspecialchars($id_usuario); ?>"
                    class="form_general" onsubmit="confirmarEdicion(event);">


                    <!-- Campo para editar el nombre -->
                    <label for="nombre" class="form-label">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="input-general" value="<?php echo htmlspecialchars($monitor['nombre']); ?>" required>

                    <!-- Campo para editar el email -->
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="input-general" value="<?php echo htmlspecialchars($monitor['email']); ?>" required>

                    <!-- Especialidades del monitor -->
                    <label class="form-label">Especialidades Actuales:</label>
                    <div class="especialidades-lista">
                        <?php if (!empty($monitor['especialidades'])): ?>
                            <ul>
                                <?php foreach ($monitor['especialidades'] as $especialidad): ?>
                                    <li><?php echo htmlspecialchars($especialidad['nombre']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No hay especialidades asignadas.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Campo para editar la experiencia -->
                    <label for="experiencia" class="form-label">Experiencia (años):</label>
                    <input type="number" id="experiencia" name="experiencia" class="input-general" value="<?php echo htmlspecialchars($monitor['experiencia']); ?>" required min="0">

                    <!-- Campo para editar la disponibilidad -->
                    <label for="disponibilidad" class="form-label">Disponibilidad:</label>
                    <select id="disponibilidad" name="disponibilidad" class="select-general" required>
                        <option value="disponible" <?php echo ($monitor['disponibilidad'] === 'disponible') ? 'selected' : ''; ?>>Disponible</option>
                        <option value="no disponible" <?php echo ($monitor['disponibilidad'] === 'no disponible') ? 'selected' : ''; ?>>No Disponible</option>
                    </select>

                    <!-- Campo para seleccionar múltiples entrenamientos con checkboxes -->
                    <label class="form-label">Asignar Especialidad:</label>
                    <div class="entrenamientos-checkboxes">
                        <?php foreach ($entrenamientos as $entrenamiento): ?>
                            <div class="entrenamiento-item">
                                <input
                                    type="checkbox"
                                    id="entrenamiento_<?php echo htmlspecialchars($entrenamiento['id_especialidad']); ?>"
                                    name="entrenamiento[]"
                                    value="<?php echo htmlspecialchars($entrenamiento['id_especialidad']); ?>"
                                    <?php echo isset($monitor['especialidades']) && in_array($entrenamiento['id_especialidad'], array_column($monitor['especialidades'], 'id_especialidad')) ? 'checked' : ''; ?>>
                                <label for="entrenamiento_<?php echo htmlspecialchars($entrenamiento['id_especialidad']); ?>">
                                    <?php echo htmlspecialchars($entrenamiento['nombre']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="button-container">
                        <button type="submit" class="btn-general">Actualizar Cambios</button>
                        <a href="<?= htmlspecialchars($_SESSION['referer']) ?>" class="btn-general btn-secondary" onclick="unsetReferer()">Volver</a>
                    </div>

                </form>
            <?php else: ?>
                <p>Monitor no encontrado.</p>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../../assets/js/validacion.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/alertas.js"></script>

</body>


</html>
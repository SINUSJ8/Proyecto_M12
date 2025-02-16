<?php
require_once('../monitores/monitor_functions.php');
require_once('../usuarios/user_functions.php');
require_once('../includes/notificaciones_functions.php');

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

if (!$monitor) {
    die("Monitor no encontrado.");
}

$id_monitor = $monitor['id_monitor'];

// **Obtener especialidades actuales del monitor**
$especialidades_actuales = [];
if (!empty($monitor['especialidades']) && is_array($monitor['especialidades'])) {
    foreach ($monitor['especialidades'] as $especialidad) {
        $especialidades_actuales[intval($especialidad['id_especialidad'])] = $especialidad['nombre'];
    }
}

// **Obtener especialidades disponibles**
$entrenamientos = obtenerEntrenamientos($conn);
if (!is_array($entrenamientos)) {
    $entrenamientos = [];
}

$mensaje = "";
$esError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $experiencia = $_POST['experiencia'] ?? null;
    $disponibilidad_nueva = $_POST['disponibilidad'] ?? null;
    $entrenamientos_seleccionados = isset($_POST['entrenamiento']) ? array_map('intval', (array)$_POST['entrenamiento']) : [];

    $errores = [];

    // **Validar nombre**
    if (!preg_match('/[a-zA-Z]/', $nombre)) {
        $errores[] = "El nombre debe contener al menos una letra.";
    }

    // **Validar email**
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del correo electrónico no es válido.";
    }

    // **Validar campos obligatorios**
    if (empty($nombre) || empty($email) || $experiencia === null || $disponibilidad_nueva === null) {
        $errores[] = "Todos los campos son obligatorios.";
    }

    // **Si hay errores, mostrar mensaje**
    if (!empty($errores)) {
        $mensaje = implode("<br>", $errores);
        $esError = true;
    } else {
        // **Verificar si la disponibilidad cambió**
        $disponibilidad_anterior = $monitor['disponibilidad'];
        $cambio_de_disponibilidad = ($disponibilidad_anterior !== $disponibilidad_nueva);

        // **Detectar especialidades agregadas y eliminadas**
        $especialidades_agregadas = [];
        $especialidades_eliminadas = [];

        foreach ($entrenamientos_seleccionados as $id_especialidad) {
            if (!array_key_exists($id_especialidad, $especialidades_actuales)) {
                $nombre_especialidad = obtenerNombreEspecialidad($conn, $id_especialidad);
                $especialidades_agregadas[$id_especialidad] = $nombre_especialidad;
            }
        }

        foreach ($especialidades_actuales as $id_especialidad => $nombre_especialidad) {
            if (!in_array($id_especialidad, $entrenamientos_seleccionados, true)) {
                // **Verificar si la especialidad tiene clases futuras**
                if (tieneClasesFuturas($conn, $id_monitor, $id_especialidad)) {
                    $errores[] = "❌ No puedes eliminar la especialidad '$nombre_especialidad' porque tiene clases asignadas en el futuro.";
                } else {
                    $especialidades_eliminadas[$id_especialidad] = $nombre_especialidad;
                }
            }
        }

        // **Si hay errores por eliminación de especialidades, mostrar mensaje y no actualizar**
        if (!empty($errores)) {
            $mensaje = implode("<br>", $errores);
            $esError = true;
        } else {
            // **Actualizar el monitor en la base de datos**
            $resultado = actualizarMonitor($conn, $id_usuario, $nombre, $email, $monitor['especialidad'], $experiencia, $disponibilidad_nueva);

            if ($resultado['success']) {
                actualizarEntrenamientosMonitor($conn, $id_monitor, $entrenamientos_seleccionados);
                $mensaje = "Monitor actualizado correctamente.";

                // **Notificar por especialidades agregadas**
                foreach ($especialidades_agregadas as $id_especialidad => $nombre_especialidad) {
                    enviarNotificacion($conn, $id_usuario, "Se te ha asignado la especialidad: $nombre_especialidad.");
                }

                // **Notificar por especialidades eliminadas**
                foreach ($especialidades_eliminadas as $id_especialidad => $nombre_especialidad) {
                    enviarNotificacion($conn, $id_usuario, "Se te ha removido la especialidad: $nombre_especialidad.");
                }

                // **Notificación por cambio de disponibilidad**
                if ($cambio_de_disponibilidad) {
                    enviarNotificacion($conn, $id_usuario, "Tu disponibilidad ha cambiado a '$disponibilidad_nueva'.");
                }

                // **Recargar los datos del monitor después de actualizar**
                $monitor = obtenerMonitorPorID($conn, $id_usuario);
            } else {
                $mensaje = "Error al actualizar el monitor.";
                $esError = true;
            }
        }
    }
}

include '../admin/admin_header.php';
?>


<body>
    <main>
        <h2 class="section-title">Editar Monitor</h2>

        <?php if (!empty($mensaje)): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        title: "<?php echo $esError ? '¡Error!' : '¡Éxito!'; ?>",
                        text: "<?php echo htmlspecialchars_decode($mensaje); ?>",
                        icon: "<?php echo $esError ? 'error' : 'success'; ?>",
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
                    <input type="text" id="nombre" name="nombre" class="input-general"
                        value="<?php echo htmlspecialchars($monitor['nombre']); ?>" required
                        title="Ingrese el nombre del monitor. Debe contener al menos una letra.">

                    <!-- Campo para editar el email -->
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="input-general"
                        value="<?php echo htmlspecialchars($monitor['email']); ?>" required
                        title="Ingrese una dirección de correo electrónico válida.">

                    <!-- Especialidades del monitor -->
                    <label class="form-label">Especialidades Actuales:</label>
                    <div class="especialidades-lista">
                        <?php if (!empty($monitor['especialidades'])): ?>
                            <ul>
                                <?php foreach ($monitor['especialidades'] as $especialidad): ?>
                                    <li title="Especialidad asignada al monitor."><?php echo htmlspecialchars($especialidad['nombre']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No hay especialidades asignadas.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Campo para editar la experiencia -->
                    <label for="experiencia" class="form-label">Experiencia (años):</label>
                    <input type="number" id="experiencia" name="experiencia" class="input-general"
                        value="<?php echo htmlspecialchars($monitor['experiencia']); ?>" required min="0"
                        title="Ingrese los años de experiencia del monitor.">

                    <!-- Campo para editar la disponibilidad -->
                    <label for="disponibilidad" class="form-label">Disponibilidad:</label>
                    <select id="disponibilidad" name="disponibilidad" class="select-general" required
                        title="Seleccione si el monitor está disponible o no.">
                        <option value="disponible" <?php echo ($monitor['disponibilidad'] === 'disponible') ? 'selected' : ''; ?>>
                            Disponible
                        </option>
                        <option value="no disponible" <?php echo ($monitor['disponibilidad'] === 'no disponible') ? 'selected' : ''; ?>>
                            No Disponible
                        </option>
                    </select>

                    <!-- Campo para seleccionar múltiples entrenamientos con checkboxes -->
                    <label class="form-label">Asignar Especialidad:</label>
                    <div class="entrenamientos-checkboxes">
                        <?php foreach ($entrenamientos as $entrenamiento): ?>
                            <div class="entrenamiento-item">
                                <input type="checkbox"
                                    id="entrenamiento_<?php echo htmlspecialchars($entrenamiento['id_especialidad']); ?>"
                                    name="entrenamiento[]"
                                    value="<?php echo htmlspecialchars($entrenamiento['id_especialidad']); ?>"
                                    <?php echo isset($monitor['especialidades']) && in_array($entrenamiento['id_especialidad'], array_column($monitor['especialidades'], 'id_especialidad')) ? 'checked' : ''; ?>
                                    title="Seleccione para asignar la especialidad <?php echo htmlspecialchars($entrenamiento['nombre']); ?> al monitor.">
                                <label for="entrenamiento_<?php echo htmlspecialchars($entrenamiento['id_especialidad']); ?>">
                                    <?php echo htmlspecialchars($entrenamiento['nombre']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="button-container">
                        <button type="submit" class="btn-general" title="Guarde los cambios realizados al monitor.">Actualizar Cambios</button>
                        <button onclick="window.history.back()" class="btn-general btn-secondary">Volver</button>
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
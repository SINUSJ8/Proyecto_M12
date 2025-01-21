<?php
require_once('../miembros/member_functions.php');

verificarAdmin();

$conn = obtenerConexion();
$title = "Editar Miembro";

// Verificar si se proporcionó el ID del usuario
if (!isset($_GET['id_usuario'])) {
    die("ID de usuario no proporcionado.");
}

$id_usuario = $_GET['id_usuario'];
$miembro = obtenerDetalleCompletoMiembro($conn, $id_usuario);

if (!$miembro) {
    die("Miembro no encontrado.");
}

// Obtener entrenamientos y membresías para los desplegables
$entrenamientos = obtenerEntrenamientos($conn);
$membresias = obtenerMembresias($conn);

// Manejo del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? null;
    $email = $_POST['email'] ?? null;
    $fecha_registro = $_POST['fecha_registro'] ?? null;
    $id_membresia_nueva = $_POST['id_membresia'] ?? null;
    $entrenamientos_seleccionados = $_POST['entrenamiento'] ?? [];
    $fecha_inicio_nueva = $_POST['fecha_inicio'] ?? null;
    $fecha_fin_nueva = $_POST['fecha_fin'] ?? null;

    if (!$nombre || !$email || !$fecha_registro || !$id_membresia_nueva) {
        $mensaje = "Error: Todos los campos son obligatorios.";
    } else {
        // Actualizar membresía
        $resultadoMembresia = actualizarMembresia($conn, $miembro['id_miembro'], $id_membresia_nueva, $fecha_inicio_nueva, $fecha_fin_nueva);

        // Actualizar entrenamientos
        try {
            actualizarEntrenamientosMiembro($conn, $miembro['id_miembro'], $entrenamientos_seleccionados);
            $mensaje = $resultadoMembresia['success']
                ? "Miembro actualizado correctamente."
                : $resultadoMembresia['message'];
        } catch (Exception $e) {
            $mensaje = "Error al actualizar los entrenamientos: " . $e->getMessage();
        }

        // Recargar los datos del miembro
        $miembro = obtenerDetalleCompletoMiembro($conn, $id_usuario);
    }
}

include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2 class="section-title">Editar Miembro</h2>

        <?php if (isset($mensaje)): ?>
            <div class="<?php echo strpos($mensaje, 'Error') === false ? 'mensaje-confirmacion' : 'mensaje-error'; ?>">
                <p><?php echo htmlspecialchars($mensaje); ?></p>
            </div>
        <?php endif; ?>

        <div class="form_container">
            <?php if ($miembro): ?>
                <form method="POST" action="edit_miembro.php?id_usuario=<?php echo htmlspecialchars($id_usuario); ?>" class="form_general" onsubmit="habilitarFechaRegistro(); return validarFormularioEdicion('miembro');">

                    <!-- Campo para editar el nombre -->
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="input-general" value="<?php echo htmlspecialchars($miembro['nombre']); ?>" required aria-label="Nombre completo del miembro">

                    <!-- Campo para editar el email -->
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="input-general" value="<?php echo htmlspecialchars($miembro['email']); ?>" required title="Introduce el email del miembro" aria-label="Correo electrónico del miembro">

                    <!-- Campo para editar la fecha de registro -->
                    <label for="fecha_registro">Fecha de Registro:</label>
                    <input type="date" id="fecha_registro" name="fecha_registro" class="input-general" value="<?php echo htmlspecialchars($miembro['fecha_registro']); ?>" required aria-label="Fecha de registro del miembro" title="Introduce la fecha de registro del miembro" disabled>

                    <!-- Checkbox para habilitar la edición de la fecha de registro -->
                    <div class="checkbox-group">
                        <input type="checkbox" id="editar_fecha" onclick="toggleFechaRegistro();">
                        <label for="editar_fecha">Editar Fecha de Registro</label>
                    </div>

                    <!-- Campo para editar el tipo de membresía -->
                    <label for="tipo_membresia">Tipo de Membresía:</label>
                    <select id="tipo_membresia" name="id_membresia" class="select-general" required onchange="actualizarFechasMembresia()">
                        <?php foreach ($membresias as $membresia): ?>
                            <option value="<?php echo htmlspecialchars($membresia['id_membresia']); ?>"
                                data-duracion="<?php echo htmlspecialchars($membresia['duracion']); ?>"
                                data-entrenamientos="<?php echo htmlspecialchars(implode(',', $membresia['entrenamientos_ids'] ?? [])); ?>"
                                <?php echo (isset($miembro['id_membresia']) && $membresia['id_membresia'] == $miembro['id_membresia']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($membresia['tipo']); ?> -
                                <?php echo "$" . htmlspecialchars($membresia['precio']); ?>
                                (<?php echo htmlspecialchars($membresia['duracion']) . " meses"; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="fecha_inicio">Fecha de Inicio de la Membresía:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="input-general" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>

                    <label for="fecha_fin">Fecha de Fin de la Membresía:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="input-general" value="<?php echo htmlspecialchars($fecha_fin); ?>" required>

                    <!-- Campo para seleccionar múltiples entrenamientos con checkboxes -->
                    <label>Entrenamientos:</label>
                    <div class="entrenamientos-checkboxes">
                        <?php foreach ($entrenamientos as $entrenamiento): ?>
                            <div class="entrenamiento-item">
                                <input
                                    type="checkbox"
                                    id="entrenamiento_<?php echo $entrenamiento['id_especialidad']; ?>"
                                    name="entrenamiento[]"
                                    value="<?php echo $entrenamiento['id_especialidad']; ?>"
                                    <?php echo in_array($entrenamiento['id_especialidad'], $miembro['entrenamientos']) ? 'checked' : ''; ?>>
                                <label for="entrenamiento_<?php echo $entrenamiento['id_especialidad']; ?>">
                                    <?php echo htmlspecialchars($entrenamiento['nombre']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="button-container">
                        <button type="submit" class="btn-general">Guardar Cambios</button>
                        <a href="<?= htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'miembros.php') ?>" class="btn-general btn-secondary">Cancelar</a>
                    </div>

                </form>
            <?php else: ?>
                <p>Miembro no encontrado.</p>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        function toggleFechaRegistro() {
            const fechaRegistroInput = document.getElementById('fecha_registro');
            fechaRegistroInput.disabled = !fechaRegistroInput.disabled;
        }

        function habilitarFechaRegistro() {
            document.getElementById('fecha_registro').disabled = false;
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectMembresia = document.getElementById('tipo_membresia');
            if (selectMembresia) {
                selectMembresia.addEventListener('change', () => {
                    actualizarFechasMembresia();
                    actualizarEntrenamientos();
                });

                actualizarFechasMembresia();
                actualizarEntrenamientos();
            }
        });
    </script>

    <script src="../assets/js/validacion.js"></script>
</body>
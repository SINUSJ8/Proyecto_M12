<?php
require_once('../miembros/member_functions.php');
require_once('../includes/notificaciones_functions.php');

verificarAdmin();
$conn = obtenerConexion();
$title = "Editar Miembro";

// Verificar si se proporcionó el ID del usuario
if (!isset($_GET['id_usuario'])) {
    die("ID de usuario no proporcionado.");
}

$id_usuario = intval($_GET['id_usuario']);
$miembro = obtenerDetalleCompletoMiembro($conn, $id_usuario);

if (!$miembro) {
    die("Miembro no encontrado.");
}

$ids_entrenamientos = $miembro['entrenamientos'];
$nombres_entrenamientos = obtenerNombresEntrenamientos($conn, $ids_entrenamientos);

// Obtener entrenamientos y membresías
$entrenamientos = obtenerEntrenamientos($conn);
$membresias = obtenerMembresias($conn);

// Fechas de membresía activa
$fechas_membresia = $miembro['fechas_membresia'] ?? ['inicio' => '', 'fin' => ''];

// Manejo del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_membresia_nueva = $_POST['id_membresia'] ?? null;
    $entrenamientos_seleccionados = $_POST['entrenamiento'] ?? [];
    $fecha_inicio_nueva = $_POST['fecha_inicio'] ?? null;
    $fecha_fin_nueva = $_POST['fecha_fin'] ?? null;

    if (!$id_membresia_nueva || !$fecha_inicio_nueva || !$fecha_fin_nueva) {
        $mensaje = "Error: Todos los campos son obligatorios.";
    } else {
        // Verificar si el miembro ya tiene una membresía activa
        $stmt = $conn->prepare("SELECT id FROM miembro_membresia WHERE id_miembro = ? AND estado = 'activa'");
        $stmt->bind_param("i", $miembro['id_miembro']);
        $stmt->execute();
        $stmt->store_result();
        $tieneMembresiaActiva = $stmt->num_rows > 0;
        $stmt->close();

        // Determinar el estado de la membresía según las fechas
        $fecha_actual = date('Y-m-d');
        if ($fecha_inicio_nueva > $fecha_actual) {
            $nuevo_estado = 'inactiva'; // Aún no empieza
        } elseif ($fecha_fin_nueva < $fecha_actual) {
            $nuevo_estado = 'expirada'; // Ya venció
        } else {
            $nuevo_estado = 'activa'; // Dentro del rango de fechas
        }

        if ($tieneMembresiaActiva) {
            // **Actualizar membresía existente**
            $stmt = $conn->prepare("UPDATE miembro_membresia SET id_membresia = ?, fecha_inicio = ?, fecha_fin = ?, estado = ? WHERE id_miembro = ?");
            $stmt->bind_param("isssi", $id_membresia_nueva, $fecha_inicio_nueva, $fecha_fin_nueva, $nuevo_estado, $miembro['id_miembro']);
            $stmt->execute();
            $stmt->close();
        } else {
            // **Crear nueva membresía con valores predeterminados**
            $monto_pagado = 0;
            $metodo_pago = 'tarjeta';
            $renovacion_automatica = false;

            $stmt = $conn->prepare("INSERT INTO miembro_membresia (id_miembro, id_membresia, monto_pagado, fecha_inicio, fecha_fin, estado, id_metodo_pago, renovacion_automatica) 
                                    VALUES (?, ?, ?, ?, ?, ?, NULL, ?)");
            $stmt->bind_param("iissssi", $miembro['id_miembro'], $id_membresia_nueva, $monto_pagado, $fecha_inicio_nueva, $fecha_fin_nueva, $nuevo_estado, $renovacion_automatica);
            $stmt->execute();
            $stmt->close();
        }

        // Obtener el nombre de la nueva membresía después de actualizar
        $stmt = $conn->prepare("SELECT tipo FROM membresia WHERE id_membresia = ?");
        $stmt->bind_param("i", $id_membresia_nueva);
        $stmt->execute();
        $result = $stmt->get_result();
        $nombreMembresia = $result->fetch_assoc()['tipo'] ?? 'Desconocida';
        $stmt->close();

        // **Actualizar entrenamientos solo si la membresía está activa, eliminarlos si no lo está**
        if ($nuevo_estado === 'activa') {
            try {
                actualizarEntrenamientosMiembro($conn, $miembro['id_miembro'], $entrenamientos_seleccionados);
                $mensaje = "Membresía y entrenamientos actualizados correctamente.";
            } catch (Exception $e) {
                $mensaje = "Error al actualizar los entrenamientos: " . $e->getMessage();
            }
        } else {
            // **Eliminar entrenamientos si la membresía es inactiva o expirada**
            $stmt = $conn->prepare("DELETE FROM miembro_entrenamiento WHERE id_miembro = ?");
            $stmt->bind_param("i", $miembro['id_miembro']);
            $stmt->execute();
            $stmt->close();
            $mensaje = "Membresía actualizada y entrenamientos eliminados porque la membresía está fuera de rango de fechas.";
        }

        // **Enviar notificación**
        $mensajeNotificacion = "Tu membresía ha sido actualizada a '{$nombreMembresia}' con fecha de inicio {$fecha_inicio_nueva} y fin {$fecha_fin_nueva}. Estado actual: {$nuevo_estado}.";
        enviarNotificacion($conn, $id_usuario, $mensajeNotificacion);

        // **Redirigir para evitar duplicación de envíos de formulario**
        header("Location: edit_miembro.php?id_usuario=" . $id_usuario . "&mensaje=" . urlencode($mensaje));
        exit();
        // Recargar datos del miembro
        $miembro = obtenerDetalleCompletoMiembro($conn, $id_usuario);
        $fechas_membresia = $miembro['fechas_membresia'] ?? ['inicio' => '', 'fin' => ''];
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
                <section class="datos-usuario">
                    <h3>Datos del Usuario</h3>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($miembro['nombre']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($miembro['email']); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($miembro['telefono'] ?? 'No disponible'); ?></p>
                    <p><strong>Entrenamientos Actuales:</strong>
                        <?php echo htmlspecialchars(implode(', ', $nombres_entrenamientos)); ?>
                    </p>
                    <a href="../usuarios/edit_usuario.php?id_usuario=<?php echo htmlspecialchars($id_usuario); ?>" class="btn-general btn-primary">Editar Usuario</a>
                </section>


                <form method="POST" action="edit_miembro.php?id_usuario=<?php echo htmlspecialchars($id_usuario); ?>" class="form_general">

                    <!-- Campo para editar el tipo de membresía -->
                    <label for="tipo_membresia">Tipo de Membresía:</label>
                    <select id="tipo_membresia" name="id_membresia" class="select-general" required onchange="actualizarEntrenamientos()">
                        <?php foreach ($membresias as $membresia): ?>
                            <option value="<?php echo htmlspecialchars($membresia['id_membresia']); ?>"
                                data-duracion="<?php echo htmlspecialchars($membresia['duracion']); ?>"
                                data-entrenamientos="<?php echo htmlspecialchars(implode(',', $membresia['entrenamientos_ids'] ?? [])); ?>"
                                <?php echo ($membresia['id_membresia'] == $miembro['id_membresia']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($membresia['tipo']); ?> -
                                <?php echo "$" . htmlspecialchars($membresia['precio']); ?>
                                (<?php echo htmlspecialchars($membresia['duracion']) . " meses"; ?>)
                            </option>

                        <?php endforeach; ?>
                    </select>

                    <!-- Fechas de la membresía -->
                    <label for="fecha_inicio">Fecha de Inicio de la Membresía:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="input-general"
                        value="<?php echo htmlspecialchars($fechas_membresia['inicio'] ?: date('Y-m-d')); ?>" required>

                    <label for="fecha_fin">Fecha de Fin de la Membresía:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="input-general" value="<?php echo htmlspecialchars($fechas_membresia['fin']); ?>" required>

                    <!-- Entrenamientos -->
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
                        <a href="<?= htmlspecialchars($_SESSION['referer'] ?? 'miembros.php') ?>" class="btn-general btn-secondary">Cancelar</a>
                    </div>
                </form>
            <?php else: ?>
                <p>Miembro no encontrado.</p>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        function actualizarEntrenamientos() {
            const selectMembresia = document.getElementById('tipo_membresia');
            const entrenamientosCheckboxes = document.querySelectorAll('.entrenamientos-checkboxes input[type="checkbox"]');
            const fechaInicioInput = document.getElementById('fecha_inicio');
            const fechaFinInput = document.getElementById('fecha_fin');

            // Obtener entrenamientos asociados con la membresía seleccionada
            const entrenamientosSeleccionados = selectMembresia.options[selectMembresia.selectedIndex].dataset.entrenamientos.split(',');
            entrenamientosCheckboxes.forEach(checkbox => {
                checkbox.checked = entrenamientosSeleccionados.includes(checkbox.value);
            });

            // Actualizar la fecha de fin según la duración de la membresía
            const duracion = parseInt(selectMembresia.options[selectMembresia.selectedIndex].dataset.duracion, 10);
            const fechaInicio = new Date();

            if (!isNaN(duracion) && duracion > 0) {
                fechaInicio.setMonth(fechaInicio.getMonth() + duracion);
                const fechaFinFormateada = fechaInicio.toISOString().split('T')[0];
                fechaFinInput.value = fechaFinFormateada;
            } else {
                fechaFinInput.value = '';
                console.warn('Duración de la membresía no válida.');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const selectMembresia = document.getElementById('tipo_membresia');

            // Actualizar entrenamientos y fechas al cargar la página y al cambiar la membresía
            actualizarEntrenamientos();
            selectMembresia.addEventListener('change', actualizarEntrenamientos);
        });
    </script>

    <script src="../../assets/js/alertas.js"></script>
</body>
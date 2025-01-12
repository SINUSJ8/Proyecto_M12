<?php
$title = "Clases Disponibles";
include '../miembros/miembro_header.php';
require_once '../clases/mi_clase_functions.php';

$conn = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];
$id_especialidad = isset($_GET['especialidad']) ? intval($_GET['especialidad']) : null;

try {
    $id_miembro = obtenerIdMiembro($conn, $id_usuario);
    // Leer la especialidad guardada para el miembro desde la base de datos 
    if (!$id_especialidad) { 
        $stmt = $conn->prepare("SELECT id_especialidad FROM miembro_entrenamiento WHERE id_miembro = ?"); 
        $stmt->bind_param("i", $id_miembro); 
        $stmt->execute(); 
        $stmt->bind_result($id_especialidad); 
        $stmt->fetch(); 
        $stmt->close(); 
    }

    // Procesar formulario para apuntarse o borrarse
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_clase = intval($_POST['id_clase']);
        $accion = $_POST['accion'];

        if ($accion === 'apuntarse') {
            if (claseEstaCompleta($conn, $id_clase)) {
                $mensaje = 'La clase está completa. No puedes inscribirte.';
            } elseif (yaInscritoEnClase($conn, $id_clase, $id_miembro)) {
                $mensaje = 'Ya estás inscrito en esta clase.';
            } else {
                $mensaje = apuntarseClase($conn, $id_clase, $id_miembro) ? '¡Te has inscrito correctamente!' : 'Error al inscribirte.';
            }
        } elseif ($accion === 'borrarse') {
            $mensaje = borrarseClase($conn, $id_clase, $id_miembro) ? 'Te has dado de baja correctamente.' : 'Error al darte de baja.';
        }
    }

    // Obtener las especialidades del miembro
    $especialidades = obtenerEspecialidadesMiembro($conn, $id_miembro);

    // Obtener las clases disponibles según las especialidades
    $clasesDisponibles = !empty($especialidades)
        ? obtenerClasesDisponibles($conn, $especialidades, $id_miembro)
        : [];

    // Obtener las clases inscritas
    $clasesInscritas = obtenerClasesInscritas($conn, $id_miembro);
} catch (Exception $e) {
    echo "<p class='mensaje-error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
// Obtener la información de la especialidad seleccionada 
    if ($id_especialidad) { 
        $stmt = $conn->prepare("SELECT * FROM especialidad WHERE id_especialidad = ?"); 
        $stmt->bind_param("i", $id_especialidad); 
        $stmt->execute(); $resultado = $stmt->get_result(); 
        $especialidadSeleccionada = $resultado->fetch_assoc(); 
        $stmt->close(); 
    } 


?>

<main>
    <h1 class="section-title">Clases Disponibles</h1>

    <!-- Mensaje de confirmación -->
    <?php if (!empty($mensaje)): ?>
        <p class="mensaje-confirmacion"><?= htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <?php if (isset($especialidadSeleccionada)): ?> 
        <h2 class="intro-text">Especialidad Seleccionada: <?= htmlspecialchars($especialidadSeleccionada['nombre']); ?>
        </h2> <?php endif; ?>

    <!-- Clases Inscritas -->
    <?php if (!empty($clasesInscritas)): ?>
        <h2 class="intro-text">Clases Inscritas</h2>
        <table id="tabla-clases-inscritas" class="styled-table">
            <thead>
                <tr>
                    <th>Nombre de la Clase</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clasesInscritas as $claseInscrita): ?>
                    <tr>
                        <td><?= htmlspecialchars($claseInscrita['nombre']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id_clase" value="<?= htmlspecialchars($claseInscrita['id_clase']); ?>">
                                <input type="hidden" name="accion" value="borrarse">
                                <button type="submit" class="btn-general delete-button">Borrarme</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="mensaje-info">No estás inscrito en ninguna clase.</p>
    <?php endif; ?>

    <!-- Clases Disponibles -->
    <?php if (!empty($clasesDisponibles)): ?>
        <h2 class="intro-text">Clases Disponibles</h2>
        <table id="tabla-clases" class="styled-table">
            <thead>
                <tr>
                    <th class="sortable" onclick="ordenarTablaC(0)">Nombre</th>
                    <th class="sortable" onclick="ordenarTablaC(1)">Especialidad</th>
                    <th class="sortable" onclick="ordenarTablaC(2)">Fecha</th>
                    <th class="sortable" onclick="ordenarTablaC(3)">Horario</th>
                    <th class="sortable" onclick="ordenarTablaC(4)">Duración</th>
                    <th class="sortable" onclick="ordenarTablaC(5)">Capacidad</th>
                    <th class="sortable" onclick="ordenarTablaC(6)">Monitor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clasesDisponibles as $clase): ?>
                    <tr>
                        <td><?= htmlspecialchars($clase['nombre']); ?></td>
                        <td><?= htmlspecialchars($clase['especialidad']); ?></td>
                        <td><?= htmlspecialchars($clase['fecha']); ?></td>
                        <td><?= htmlspecialchars($clase['horario']); ?></td>
                        <td><?= htmlspecialchars($clase['duracion']); ?> minutos</td>
                        <td><?= htmlspecialchars($clase['capacidad_maxima']); ?></td>
                        <td><?= htmlspecialchars($clase['monitor'] ?? ''); ?></td>
                        <td>
                            <?php if ($clase['inscrito']): ?>
                                <span class="mensaje-inscrito">Ya inscrito</span>
                            <?php elseif ($clase['completa']): ?>
                                <span class="mensaje-completa">Completa</span>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id_clase" value="<?= htmlspecialchars($clase['id_clase']); ?>">
                                    <input type="hidden" name="accion" value="apuntarse">
                                    <button type="submit" class="btn-general">Apuntarme</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="mensaje-info">No hay clases disponibles para tus especialidades.</p>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
<script src="../../assets/js/clases.js"></script>
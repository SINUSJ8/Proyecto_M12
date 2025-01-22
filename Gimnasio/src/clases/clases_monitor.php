<?php
require_once('../includes/general.php');
require_once('../miembros/member_functions.php');
require_once('class_functions.php');

// Verificar que el usuario es un monitor
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'monitor') {
    header("Location: ../index.php?error=Acceso+denegado");
    exit();
}

$conn = obtenerConexion();
$title = "Clases Asignadas";
$id_monitor = $_SESSION['id_usuario'];

// Manejar la eliminación de participantes
// Manejar la eliminación de participantes
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === 'eliminar_participante') {
    $id_clase = intval($_POST['id_clase']);
    $id_miembro = intval($_POST['id_miembro']);

    // Obtener el nombre de la clase para la notificación
    $sqlClase = "SELECT nombre FROM clase WHERE id_clase = ?";
    $stmtClase = $conn->prepare($sqlClase);
    $stmtClase->bind_param("i", $id_clase);
    $stmtClase->execute();
    $nombreClase = $stmtClase->get_result()->fetch_assoc()['nombre'] ?? 'Clase desconocida';
    $stmtClase->close();

    // Llamar a la función para eliminar al participante
    $resultado = eliminarParticipanteDeClase($conn, $id_clase, $id_miembro, $id_monitor);

    if ($resultado['success']) {
        // Enviar notificación al miembro afectado
        $mensaje = "Has sido eliminado de la clase '{$nombreClase}'. Si crees que esto es un error, por favor, contacta al gimnasio.";
        enviarNotificacion($conn, $id_miembro, $mensaje);
    }

    // Redirigir con el mensaje del resultado
    header("Location: clases_monitor.php?mensaje=" . urlencode($resultado['mensaje']));
    exit();
}


// Obtener clases asignadas al monitor
$sqlClases = "
    SELECT c.id_clase, c.nombre AS clase_nombre, e.nombre AS especialidad, c.fecha, c.horario, c.duracion
    FROM clase c
    INNER JOIN monitor m ON c.id_monitor = m.id_monitor
    INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
    WHERE m.id_usuario = ?
    ORDER BY c.fecha, c.horario
";
$stmt = $conn->prepare($sqlClases);
$stmt->bind_param("i", $id_monitor);
$stmt->execute();
$resultClases = $stmt->get_result();
$clases = $resultClases->fetch_all(MYSQLI_ASSOC);

include '../monitores/monitores_header.php';
?>

<main>
    <h2 class="section-title">Clases Asignadas</h2>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="mensaje-confirmacion"><?php echo htmlspecialchars($_GET['mensaje']); ?></div>
    <?php endif; ?>

    <?php if (empty($clases)): ?>
        <p class="mensaje-info">No tienes clases asignadas.</p>
    <?php else: ?>
        <div class="clases-grid">
            <?php foreach ($clases as $clase): ?>
                <div class="clase-card">
                    <h3 class="clase-titulo" title="Nombre de la clase"><?php echo htmlspecialchars($clase['clase_nombre']); ?></h3>
                    <p><strong>Especialidad:</strong>
                        <span title="Especialidad de la clase"><?php echo htmlspecialchars($clase['especialidad']); ?></span>
                    </p>
                    <p><strong>Fecha:</strong>
                        <span title="Fecha programada para esta clase"><?php echo htmlspecialchars($clase['fecha']); ?></span>
                    </p>
                    <p><strong>Hora:</strong>
                        <span title="Hora de inicio de la clase"><?php echo htmlspecialchars($clase['horario']); ?></span>
                    </p>
                    <p><strong>Duración:</strong>
                        <span title="Duración total de la clase en minutos"><?php echo htmlspecialchars($clase['duracion']); ?> minutos</span>
                    </p>


                    <!-- Lista de participantes -->
                    <h4 class="participantes-titulo">Participantes:</h4>
                    <?php
                    $participantes = obtenerParticipantesClase($conn, $clase['id_clase']);
                    if (empty($participantes)):
                    ?>
                        <p class="mensaje-info">No hay participantes inscritos en esta clase.</p>
                    <?php else: ?>
                        <ul class="participantes-lista">
                            <?php foreach ($participantes as $participante): ?>
                                <li class="participante-item">
                                    <?php echo htmlspecialchars($participante['nombre']); ?> -
                                    <em><?php echo htmlspecialchars($participante['email']); ?></em>

                                    <!-- Botón para eliminar participante -->
                                    <form method="POST" action="clases_monitor.php" style="display:inline;">
                                        <input type="hidden" name="accion" value="eliminar_participante">
                                        <input type="hidden" name="id_clase" value="<?php echo $clase['id_clase']; ?>">
                                        <input type="hidden" name="id_miembro" value="<?php echo $participante['id_miembro']; ?>">
                                        <button type="submit" class="delete-button"
                                            title="Eliminar a <?php echo htmlspecialchars($participante['nombre']); ?> de la clase <?php echo htmlspecialchars($clase['clase_nombre']); ?>"
                                            onclick="return confirm('¿Estás seguro de que deseas eliminar a este participante de la clase?')">
                                            Eliminar
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
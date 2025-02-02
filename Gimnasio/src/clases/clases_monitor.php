<?php
require_once('../includes/general.php');
require_once('../miembros/member_functions.php');
require_once('class_functions.php');

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'monitor') {
    header("Location: ../index.php?error=Acceso+denegado");
    exit();
}

$conn = obtenerConexion();
$title = "Clases Asignadas";
$id_monitor = $_SESSION['id_usuario'];

// Manejar la eliminación de participantes y enviar notificación
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === 'eliminar_participante') {
    $id_clase = intval($_POST['id_clase']);
    $id_miembro = intval($_POST['id_miembro']);

    // Obtener nombre de la clase
    $sqlClase = "SELECT nombre FROM clase WHERE id_clase = ?";
    $stmtClase = $conn->prepare($sqlClase);
    $stmtClase->bind_param("i", $id_clase);
    $stmtClase->execute();
    $nombreClase = $stmtClase->get_result()->fetch_assoc()['nombre'] ?? 'Clase desconocida';
    $stmtClase->close();

    // Obtener ID del usuario del miembro eliminado
    $sqlUsuario = "SELECT id_usuario FROM miembro WHERE id_miembro = ?";
    $stmtUsuario = $conn->prepare($sqlUsuario);
    $stmtUsuario->bind_param("i", $id_miembro);
    $stmtUsuario->execute();
    $resultUsuario = $stmtUsuario->get_result();
    $id_usuario_miembro = $resultUsuario->fetch_assoc()['id_usuario'] ?? null;
    $stmtUsuario->close();

    // Eliminar al participante de la clase
    $stmt = $conn->prepare("DELETE FROM asistencia WHERE id_clase = ? AND id_miembro = ?");
    $stmt->bind_param("ii", $id_clase, $id_miembro);
    $stmt->execute();
    $eliminado = $stmt->affected_rows > 0;
    $stmt->close();

    // Enviar notificación al usuario del miembro eliminado
    if ($eliminado && $id_usuario_miembro) {
        $mensaje = "Has sido eliminado de la clase '{$nombreClase}'. Si crees que esto es un error, por favor, contacta al gimnasio.";
        enviarNotificacion($conn, $id_usuario_miembro, $mensaje);
    }

    // Redirigir con mensaje
    $mensaje = $eliminado ? "Participante eliminado correctamente." : "No se pudo eliminar el participante.";
    header("Location: clases_monitor.php?mensaje=" . urlencode($mensaje));
    exit();
}

// Configuración de paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Contar total de clases asignadas al monitor
$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM clase c
    INNER JOIN monitor m ON c.id_monitor = m.id_monitor
    WHERE m.id_usuario = ?
";
$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->bind_param("i", $id_monitor);
$stmtTotal->execute();
$resultTotal = $stmtTotal->get_result()->fetch_assoc();
$total_clases = $resultTotal['total'];
$total_pages = ceil($total_clases / $limit);

// Obtener clases asignadas con paginación
$sqlClases = "
    SELECT c.id_clase, c.nombre AS clase_nombre, e.nombre AS especialidad, c.fecha, c.horario, c.duracion
    FROM clase c
    INNER JOIN monitor m ON c.id_monitor = m.id_monitor
    INNER JOIN especialidad e ON c.id_especialidad = e.id_especialidad
    WHERE m.id_usuario = ?
    ORDER BY c.fecha, c.horario
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sqlClases);
$stmt->bind_param("iii", $id_monitor, $limit, $offset);
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
                    <h3 class="clase-titulo"><?php echo htmlspecialchars($clase['clase_nombre']); ?></h3>
                    <p><strong>Especialidad:</strong> <?php echo htmlspecialchars($clase['especialidad']); ?></p>
                    <p><strong>Fecha:</strong> <?php echo htmlspecialchars($clase['fecha']); ?></p>
                    <p><strong>Hora:</strong> <?php echo htmlspecialchars($clase['horario']); ?></p>
                    <p><strong>Duración:</strong> <?php echo htmlspecialchars($clase['duracion']); ?> minutos</p>

                    <!-- Lista de participantes con scroll -->
                    <h4 class="participantes-titulo">Participantes:</h4>
                    <div class="participantes-container">
                        <?php
                        $participantes = obtenerParticipantesClase($conn, $clase['id_clase']);
                        if (empty($participantes)): ?>
                            <p class="mensaje-info">No hay participantes inscritos en esta clase.</p>
                        <?php else: ?>
                            <ul class="participantes-lista">
                                <?php foreach ($participantes as $participante): ?>
                                    <li class="participante-item">
                                        <?php echo htmlspecialchars($participante['nombre']); ?> - <em><?php echo htmlspecialchars($participante['email']); ?></em>
                                        <form method="POST" action="clases_monitor.php" style="display:inline;">
                                            <input type="hidden" name="accion" value="eliminar_participante">
                                            <input type="hidden" name="id_clase" value="<?php echo $clase['id_clase']; ?>">
                                            <input type="hidden" name="id_miembro" value="<?php echo $participante['id_miembro']; ?>">
                                            <button type="submit" class="delete-button" onclick="return confirm('¿Estás seguro de eliminar a este participante?')">
                                                Eliminar
                                            </button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="clases_monitor.php?page=<?= $page - 1; ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="clases_monitor.php?page=<?= $i; ?>" class="btn-general <?= $i === $page ? 'active' : ''; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="clases_monitor.php?page=<?= $page + 1; ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
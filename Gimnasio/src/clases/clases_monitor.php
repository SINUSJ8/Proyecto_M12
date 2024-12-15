<?php
require_once('../includes/general.php');
require_once('../miembros/member_functions.php');

// Verificar que el usuario es un monitor
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'monitor') {
    header("Location: ../index.php?error=Acceso+denegado");
    exit();
}

$conn = obtenerConexion();
$title = "Clases Asignadas";
$id_monitor = $_SESSION['id_usuario'];

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

// Obtener participantes por clase
function obtenerParticipantesClase($conn, $id_clase)
{
    $sql = "
        SELECT u.nombre, u.email
        FROM asistencia a
        INNER JOIN miembro m ON a.id_miembro = m.id_miembro
        INNER JOIN usuario u ON m.id_usuario = u.id_usuario
        WHERE a.id_clase = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_clase);
    $stmt->execute();
    $result = $stmt->get_result();
    $participantes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $participantes;
}

include '../monitores/monitores_header.php';
?>

<main>
    <h2 class="section-title">Clases Asignadas</h2>

    <?php if (empty($clases)): ?>
        <p class="mensaje-info">No tienes clases asignadas.</p>
    <?php else: ?>
        <?php foreach ($clases as $clase): ?>
            <div class="clase-container">
                <h3 class="clase-titulo"><?php echo htmlspecialchars($clase['clase_nombre']); ?></h3>
                <p><strong>Especialidad:</strong> <?php echo htmlspecialchars($clase['especialidad']); ?></p>
                <p><strong>Fecha:</strong> <?php echo htmlspecialchars($clase['fecha']); ?></p>
                <p><strong>Hora:</strong> <?php echo htmlspecialchars($clase['horario']); ?></p>
                <p><strong>Duración:</strong> <?php echo htmlspecialchars($clase['duracion']); ?> minutos</p>

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
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
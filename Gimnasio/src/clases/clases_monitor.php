<?php
require_once('../includes/general.php');
require_once('../miembros/member_functions.php');
require_once('class_functions.php');
require_once('../includes/notificaciones_functions.php');

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

    // Obtener detalles de la clase y el usuario antes de la eliminación
    $detalles_clase = obtenerDetallesClase($conn, $id_clase);
    $nombreClase = $detalles_clase['nombre'] ?? 'Clase desconocida';

    $stmtUsuario = $conn->prepare("SELECT id_usuario FROM miembro WHERE id_miembro = ?");
    $stmtUsuario->bind_param("i", $id_miembro);
    $stmtUsuario->execute();
    $resultUsuario = $stmtUsuario->get_result();
    $id_usuario_miembro = $resultUsuario->fetch_assoc()['id_usuario'] ?? null;
    $stmtUsuario->close();

    // Eliminar participante y enviar notificación si procede
    $stmt = $conn->prepare("DELETE FROM asistencia WHERE id_clase = ? AND id_miembro = ?");
    $stmt->bind_param("ii", $id_clase, $id_miembro);
    $stmt->execute();
    $eliminado = $stmt->affected_rows > 0;
    $stmt->close();

    if ($eliminado && $id_usuario_miembro) {
        $mensaje = "Has sido eliminado de la clase '{$nombreClase}'. Si crees que esto es un error, por favor, contacta al gimnasio.";
        enviarNotificacion($conn, $id_usuario_miembro, $mensaje);
    }

    $mensaje = $eliminado ? "Participante eliminado correctamente." : "No se pudo eliminar el participante.";
    header("Location: clases_monitor.php?mensaje=" . urlencode($mensaje));
    exit();
}

// Configuración de paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Obtener el total de clases y calcular páginas
$total_clases = obtenerTotalClasesMonitor($conn, $id_monitor);
$total_pages = ceil($total_clases / $limit);

// Obtener clases asignadas al monitor con paginación
$clases = obtenerClasesPaginadasMonitor($conn, $id_monitor, $limit, $offset);

include '../monitores/monitores_header.php';
?>


<main>
    <h2 class="section-title">Clases Asignadas</h2>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="mensaje-confirmacion" title="Mensaje de confirmación sobre la acción realizada.">
            <?php echo htmlspecialchars($_GET['mensaje']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($clases)): ?>
        <p class="mensaje-info" title="No tienes ninguna clase asignada en este momento.">No tienes clases asignadas.</p>
    <?php else: ?>
        <div class="clases-grid">
            <?php foreach ($clases as $clase): ?>
                <?php
                $participantes = obtenerParticipantesClase($conn, $clase['id_clase']);
                $num_inscritos = count($participantes); // Contamos los inscritos
                $capacidad_maxima = obtenerCapacidadClase($conn, $clase['id_clase']); // Obtener la capacidad de la clase
                ?>
                <div class="clase-card" title="Información detallada de la clase asignada.">
                    <h3 class="clase-titulo" title="Nombre de la clase."><?php echo htmlspecialchars($clase['clase_nombre']); ?></h3>
                    <p title="Especialidad a la que pertenece la clase."><strong>Especialidad:</strong> <?php echo htmlspecialchars($clase['especialidad']); ?></p>
                    <p title="Fecha en la que se impartirá la clase."><strong>Fecha:</strong> <?php echo date("d/m/Y", strtotime($clase['fecha'])); ?></p>
                    <p title="Hora de inicio de la clase."><strong>Hora:</strong> <?php echo htmlspecialchars($clase['horario']); ?></p>
                    <p title="Duración total de la clase en minutos."><strong>Duración:</strong> <?php echo htmlspecialchars($clase['duracion']); ?> minutos</p>
                    <p title="Número de participantes inscritos en la clase y la capacidad máxima permitida.">
                        <strong>Participantes:</strong> <?= $num_inscritos; ?> / <?= $capacidad_maxima; ?>
                    </p>

                    <!-- Lista de participantes con scroll -->
                    <h4 class="participantes-titulo" title="Lista de miembros inscritos en la clase.">Participantes:</h4>
                    <div class="participantes-container">
                        <?php if (empty($participantes)): ?>
                            <p class="mensaje-info" title="Ningún miembro se ha inscrito en esta clase aún.">
                                No hay participantes inscritos en esta clase.
                            </p>
                        <?php else: ?>
                            <ul class="participantes-lista">
                                <?php foreach ($participantes as $participante): ?>
                                    <li class="participante-item" title="Información del participante inscrito en la clase.">
                                        <span><?php echo htmlspecialchars($participante['nombre']); ?> - <em><?php echo htmlspecialchars($participante['email']); ?></em></span>
                                        <form method="POST" action="clases_monitor.php">
                                            <input type="hidden" name="accion" value="eliminar_participante">
                                            <input type="hidden" name="id_clase" value="<?php echo $clase['id_clase']; ?>">
                                            <input type="hidden" name="id_miembro" value="<?php echo $participante['id_miembro']; ?>">
                                            <button type="button" class="delete-button2" title="Eliminar este participante de la clase.">X</button>
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
        <div class="pagination" title="Navega entre las páginas de clases asignadas.">
            <?php if ($page > 1): ?>
                <a href="clases_monitor.php?page=<?= $page - 1; ?>" class="btn-general" title="Ver página anterior de clases.">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="clases_monitor.php?page=<?= $i; ?>" class="btn-general <?= $i === $page ? 'active' : ''; ?>" title="Ir a la página <?= $i; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="clases_monitor.php?page=<?= $page + 1; ?>" class="btn-general" title="Ver página siguiente de clases.">Siguiente</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const deleteButtons = document.querySelectorAll(".delete-button2");

        deleteButtons.forEach(button => {
            button.addEventListener("click", function() {
                const form = this.closest("form");

                Swal.fire({
                    title: "¿Eliminar participante?",
                    text: "Esta acción no se puede deshacer.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar"
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const mensajeConfirmacion = document.querySelector(".mensaje-confirmacion");
        if (mensajeConfirmacion) {
            setTimeout(() => {
                mensajeConfirmacion.style.opacity = "0";
                setTimeout(() => {
                    mensajeConfirmacion.style.display = "none";
                }, 500);
            }, 5000);
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
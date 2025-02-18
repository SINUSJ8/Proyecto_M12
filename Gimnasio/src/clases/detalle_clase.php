<?php
require_once '../clases/class_functions.php';
require_once('../admin/admin_functions.php');
require_once('../includes/notificaciones_functions.php');

verificarAdmin();

$conn = obtenerConexion();

// Verificar que se recibió el id_clase
if (!isset($_GET['id_clase']) || !is_numeric($_GET['id_clase'])) {
    header("Location: buscar_clase.php?error=Clase+no+especificada");
    exit();
}

$id_clase = intval($_GET['id_clase']);
$clase = obtenerDetallesClase($conn, $id_clase);
$miembros = obtenerMiembrosInscritos($conn, $id_clase);

// Manejar eliminación de participantes y enviar notificación
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['accion']) && $_POST['accion'] === 'eliminar_participante') {
    $id_miembro = intval($_POST['id_miembro']);

    // Obtener ID del usuario del miembro eliminado
    $stmtUsuario = $conn->prepare("SELECT id_usuario FROM miembro WHERE id_miembro = ?");
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

    // Enviar notificación al usuario eliminado
    if ($eliminado && $id_usuario_miembro) {
        $mensaje = "Has sido eliminado de la clase '{$clase['nombre']}' por un administrador. Si crees que esto es un error, por favor, contacta al gimnasio.";
        enviarNotificacion($conn, $id_usuario_miembro, $mensaje);
    }

    $mensaje = $eliminado ? "Participante eliminado correctamente." : "No se pudo eliminar el participante.";

    header("Location: detalle_clase.php?id_clase={$id_clase}&mensaje=" . urlencode($mensaje));
    exit();
}

$title = "Detalle de la Clase";
include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2>Detalle de la Clase: <?= htmlspecialchars($clase['nombre']); ?></h2>
        <p><strong>Especialidad:</strong> <?= htmlspecialchars($clase['especialidad']); ?></p>
        <p><strong>Monitor:</strong> <?= htmlspecialchars($clase['monitor']); ?></p>
        <p><strong>Fecha:</strong> <?= date('d-m-Y', strtotime($clase['fecha'])); ?></p>
        <p><strong>Horario:</strong> <?= htmlspecialchars($clase['horario']); ?></p>
        <p><strong>Duración:</strong> <?= htmlspecialchars($clase['duracion']); ?> min</p>
        <p><strong>Capacidad Máxima:</strong> <?= htmlspecialchars($clase['capacidad_maxima']); ?></p>
        <p><strong>Miembros Inscritos:</strong> <?= count($miembros); ?> / <?= htmlspecialchars($clase['capacidad_maxima']); ?></p>

        <h3>Miembros Apuntados</h3>

        <?php if (isset($_GET['mensaje'])): ?>
            <div class="mensaje-confirmacion">
                <?= htmlspecialchars($_GET['mensaje']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($miembros)): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($miembros as $miembro): ?>
                        <?php
                        // Obtener el id_miembro basado en el id_usuario del miembro
                        $stmt = $conn->prepare("SELECT id_miembro FROM miembro WHERE id_usuario = ?");
                        $stmt->bind_param("i", $miembro['id_usuario']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $id_miembro = $result->fetch_assoc()['id_miembro'] ?? null;
                        $stmt->close();
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($miembro['nombre']); ?></td>
                            <td><?= htmlspecialchars($miembro['email']); ?></td>
                            <td>
                                <?php if ($id_miembro): ?>
                                    <form method="POST" action="detalle_clase.php?id_clase=<?= $id_clase; ?>">
                                        <input type="hidden" name="accion" value="eliminar_participante">
                                        <input type="hidden" name="id_miembro" value="<?= htmlspecialchars($id_miembro); ?>">
                                        <button type="submit" class="delete-button2">Eliminar</button>
                                    </form>
                                <?php else: ?>
                                    <p>Error: No se encontró el ID del miembro.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        <?php else: ?>
            <p>No hay miembros inscritos en esta clase.</p>
        <?php endif; ?>

        <div class="button-container">
            <button type="button" onclick="window.history.back()" class="btn-general btn-secondary">Volver</button>

        </div>



    </main>
</body>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".delete-button2").forEach(button => {
            button.addEventListener("click", function(event) {
                event.preventDefault();
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
<?php
require_once('../includes/general.php');
require_once('../includes/notificaciones_functions.php');

// Verificar que el usuario es monitor
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'monitor') {
    header("Location: ../index.php?error=Acceso+denegado");
    exit();
}

$conn = obtenerConexion();
$title = "Mis Notificaciones";
$id_usuario = $_SESSION['id_usuario'];

// Manejar acciones de ocultar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id_notificacion = $_POST['id_notificacion'] ?? null;

    if ($_POST['accion'] === 'ocultar' && $id_notificacion) {
        ocultarNotificacion($conn, $id_notificacion, $id_usuario);
    } elseif ($_POST['accion'] === 'ocultar_todas') {
        ocultarTodasNotificaciones($conn, $id_usuario);
    }
    header("Location: monitores_notificaciones.php");
    exit();
}

// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Obtener el número total de notificaciones visibles
$total_query = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM notificacion n
    LEFT JOIN notificacion_oculta no ON n.id_notificacion = no.id_notificacion AND no.id_usuario = ?
    WHERE n.id_usuario = ? AND no.id_oculta IS NULL
");
$total_query->bind_param("ii", $id_usuario, $id_usuario);
$total_query->execute();
$total_result = $total_query->get_result()->fetch_assoc();
$total_notificaciones = $total_result['total'];
$total_pages = ceil($total_notificaciones / $limit);

// Obtener las notificaciones visibles
$query = $conn->prepare("
    SELECT n.id_notificacion, n.mensaje, n.fecha, n.leida
    FROM notificacion n
    LEFT JOIN notificacion_oculta no ON n.id_notificacion = no.id_notificacion AND no.id_usuario = ?
    WHERE n.id_usuario = ? AND no.id_oculta IS NULL
    ORDER BY n.fecha DESC 
    LIMIT ? OFFSET ?
");
$query->bind_param("iiii", $id_usuario, $id_usuario, $limit, $offset);
$query->execute();
$notificaciones = $query->get_result()->fetch_all(MYSQLI_ASSOC);

// Marcar las notificaciones como leídas
marcarNotificacionesComoLeidas($conn, $id_usuario);

include 'monitores_header.php';
?>

<main class="form_container">
    <h2 class="section-title">Mis Notificaciones</h2>

    <form method="POST">
        <button type="submit" name="accion" value="ocultar_todas" class="btn-general btn-danger">Eliminar Todas</button>
    </form>

    <?php if (empty($notificaciones)): ?>
        <p class="mensaje-info">No tienes notificaciones.</p>
    <?php else: ?>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notificaciones as $notificacion): ?>
                    <tr class="<?php echo $notificacion['leida'] ? 'notificacion-leida' : 'notificacion-nueva'; ?>">
                        <td><?php echo htmlspecialchars($notificacion['mensaje']); ?></td>
                        <td><?php echo date("d-m-Y", strtotime($notificacion['fecha'])); ?></td>
                        <td><?php echo $notificacion['leida'] ? 'Leída' : 'Nueva'; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id_notificacion" value="<?php echo $notificacion['id_notificacion']; ?>">
                                <button type="submit" name="accion" value="ocultar" class="btn-general">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="monitores_notificaciones.php?page=<?php echo $page - 1; ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="monitores_notificaciones.php?page=<?php echo $i; ?>" class="btn-general <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="monitores_notificaciones.php?page=<?php echo $page + 1; ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
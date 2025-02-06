<?php
require_once('../includes/general.php');
require_once('../includes/notificaciones_functions.php');

// Verificar que el usuario es miembro
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'usuario') {
    header("Location: ../index.php?error=Acceso+denegado");
    exit();
}

$conn = obtenerConexion();
$title = "Mis Notificaciones";
$id_usuario = $_SESSION['id_usuario'];

// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Número de notificaciones por página
$offset = ($page - 1) * $limit;

// Obtener el número total de notificaciones del usuario
$total_query = $conn->prepare("SELECT COUNT(*) AS total FROM notificacion WHERE id_usuario = ?");
$total_query->bind_param("i", $id_usuario);
$total_query->execute();
$total_result = $total_query->get_result()->fetch_assoc();
$total_notificaciones = $total_result['total'];
$total_pages = ceil($total_notificaciones / $limit);

// Obtener las notificaciones con límite y offset
$query = $conn->prepare("
    SELECT mensaje, fecha, leida 
    FROM notificacion 
    WHERE id_usuario = ? 
    ORDER BY fecha DESC 
    LIMIT ? OFFSET ?
");
$query->bind_param("iii", $id_usuario, $limit, $offset);
$query->execute();
$notificaciones = $query->get_result()->fetch_all(MYSQLI_ASSOC);

// Marcar las notificaciones como leídas
marcarNotificacionesComoLeidas($conn, $id_usuario);

include 'user_header.php';
?>

<main class="form_container">
    <h2 class="section-title">Mis Notificaciones</h2>

    <?php if (empty($notificaciones)): ?>
        <p class="mensaje-info">No tienes notificaciones.</p>
    <?php else: ?>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notificaciones as $notificacion): ?>
                    <tr class="<?php echo $notificacion['leida'] ? 'notificacion-leida' : 'notificacion-nueva'; ?>">
                        <td><?php echo htmlspecialchars($notificacion['mensaje']); ?></td>
                        <td><?php echo htmlspecialchars($notificacion['fecha']); ?></td>
                        <td><?php echo $notificacion['leida'] ? 'Leída' : 'Nueva'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Paginación -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo basename(__FILE__); ?>?page=<?php echo $page - 1; ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="<?php echo basename(__FILE__); ?>?page=<?php echo $i; ?>" class="btn-general <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="<?php echo basename(__FILE__); ?>?page=<?php echo $page + 1; ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
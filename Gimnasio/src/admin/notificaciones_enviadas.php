<?php
require_once('../includes/general.php');
verificarAdmin();

$conn = obtenerConexion();
$title = "Notificaciones enviadas";
// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Obtener notificaciones
$result = $conn->prepare("
    SELECT n.id_notificacion, u.nombre, n.mensaje, n.fecha, n.leida
    FROM notificacion n
    INNER JOIN usuario u ON n.id_usuario = u.id_usuario
    ORDER BY n.fecha DESC
    LIMIT ? OFFSET ?
");
$result->bind_param("ii", $limit, $offset);
$result->execute();
$notificaciones = $result->get_result()->fetch_all(MYSQLI_ASSOC);

// Total de notificaciones
$count_result = $conn->query("SELECT COUNT(*) AS total FROM notificacion");
$total_notificaciones = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_notificaciones / $limit);

include 'admin_header.php';
?>

<main>
    <h2 class="section-title">Notificaciones Enviadas</h2>
    <table class="styled-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Mensaje</th>
                <th>Fecha</th>
                <th>Leída</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notificaciones as $notificacion): ?>
                <tr>
                    <td><?php echo htmlspecialchars($notificacion['id_notificacion']); ?></td>
                    <td><?php echo htmlspecialchars($notificacion['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($notificacion['mensaje']); ?></td>
                    <td><?php echo htmlspecialchars($notificacion['fecha']); ?></td>
                    <td><?php echo $notificacion['leida'] ? 'Sí' : 'No'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="notificaciones_enviadas.php?page=<?php echo $page - 1; ?>" class="btn-general">Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="notificaciones_enviadas.php?page=<?php echo $i; ?>" class="btn-general <?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="notificaciones_enviadas.php?page=<?php echo $page + 1; ?>" class="btn-general">Siguiente</a>
        <?php endif; ?>

    </div>
    <div class="pagination">
        <a href="notificaciones.php" class="btn-general" style="margin-top: 10px">Volver a Notificaciones</a>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
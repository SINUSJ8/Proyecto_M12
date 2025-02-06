<?php
require_once('../includes/general.php');
require_once('../includes/notificaciones_functions.php');

verificarAdmin();
$conn = obtenerConexion();
$title = "Notificaciones enviadas";

// Manejo de eliminación y restauración de notificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id_notificacion = $_POST['id_notificacion'] ?? null;

    if ($id_notificacion) {
        if ($_POST['accion'] === 'restaurar') {
            $stmt = $conn->prepare("DELETE FROM notificacion_oculta WHERE id_notificacion = ?");
            $stmt->bind_param("i", $id_notificacion);
            $stmt->execute();
            $stmt->close();
            $success = "Notificación restaurada correctamente.";
        } elseif ($_POST['accion'] === 'eliminar') {
            $stmt = $conn->prepare("DELETE FROM notificacion WHERE id_notificacion = ?");
            $stmt->bind_param("i", $id_notificacion);
            $stmt->execute();
            $stmt->close();
            $success = "Notificación eliminada correctamente.";
        }
    }
}

// Capturar filtros de búsqueda
$buscar = $_GET['buscar'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Obtener notificaciones con los filtros
$notificaciones = obtenerNotificacionesEnviadas($conn, $buscar, $fecha_inicio, $fecha_fin, $limit, $offset);

// Total de notificaciones
$count_query = "SELECT COUNT(*) AS total FROM notificacion WHERE 1=1";

// Aplicar filtros al contador
$params = [];
$types = "";

if (!empty($buscar)) {
    $count_query .= " AND (id_usuario IN (SELECT id_usuario FROM usuario WHERE nombre LIKE ? OR email LIKE ?))";
    $buscar_param = "%$buscar%";
    $params[] = $buscar_param;
    $params[] = $buscar_param;
    $types .= "ss";
}

if (!empty($fecha_inicio)) {
    $count_query .= " AND fecha >= ?";
    $params[] = $fecha_inicio;
    $types .= "s";
}

if (!empty($fecha_fin)) {
    $count_query .= " AND fecha <= ?";
    $params[] = $fecha_fin;
    $types .= "s";
}

$count_stmt = $conn->prepare($count_query);
if (!empty($types)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_notificaciones = $count_result['total'];
$total_pages = ceil($total_notificaciones / $limit);

include 'admin_header.php';
?>


<main>
    <h2 class="section-title">Notificaciones Enviadas</h2>
    <?php if (isset($success)): ?>
        <div class="mensaje-confirmacion"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="GET" action="notificaciones_enviadas.php" class="form_container">
        <label for="buscar">Buscar por nombre o correo:</label>
        <input type="text" name="buscar" id="buscar" class="input-general"
            value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>"
            placeholder="Ingrese nombre o correo">

        <label for="fecha_inicio">Fecha inicio:</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="input-general"
            value="<?php echo isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : ''; ?>">

        <label for="fecha_fin">Fecha fin:</label>
        <input type="date" name="fecha_fin" id="fecha_fin" class="input-general"
            value="<?php echo isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : ''; ?>">

        <div class="button-group">
            <button type="submit" class="btn-general">Filtrar</button>
            <a href="notificaciones_enviadas.php" class="btn-general btn-secondary">Limpiar</a>
        </div>
    </form>



    <table class="styled-table">
        <thead>
            <tr>
                <th>Correo Electrónico</th>
                <th>Usuario</th>
                <th>Mensaje</th>
                <th>Fecha</th>
                <th>Leída</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notificaciones as $notificacion): ?>
                <tr>
                    <td><?php echo htmlspecialchars($notificacion['email']); ?></td>
                    <td><?php echo htmlspecialchars($notificacion['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($notificacion['mensaje']); ?></td>
                    <td><?php echo htmlspecialchars($notificacion['fecha']); ?></td>
                    <td><?php echo $notificacion['leida'] ? 'Sí' : 'No'; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_notificacion" value="<?php echo $notificacion['id_notificacion']; ?>">

                            <?php if ($notificacion['esta_oculta'] > 0): ?>

                                <button type="submit" name="accion" value="restaurar" class="btn-general">Restaurar</button>
                            <?php endif; ?>

                            <button type="submit" name="accion" value="eliminar" class="btn-general btn-danger">Eliminar</button>
                        </form>
                    </td>

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
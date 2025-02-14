<?php

require_once('../includes/general.php');
require_once('../usuarios/user_functions.php');

verificarAdmin(); // Verificar que el usuario es administrador

$conn = obtenerConexion();
$title = "Historial de Pagos";

// Capturar los filtros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Construcción de la consulta con filtros dinámicos
$sql = "
    SELECT 
        u.nombre AS usuario_nombre, 
        u.email AS usuario_email, 
        p.fecha_pago, 
        p.monto, 
        p.metodo_pago
    FROM pago p
    INNER JOIN miembro m ON p.id_miembro = m.id_miembro
    INNER JOIN usuario u ON m.id_usuario = u.id_usuario
    WHERE 1=1";

// Agregar condiciones según los filtros
$params = [];
$types = '';

if ($busqueda) {
    $sql .= " AND u.nombre LIKE ?";
    $params[] = "%$busqueda%";
    $types .= 's';
}
if ($fecha_inicio) {
    $sql .= " AND p.fecha_pago >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}
if ($fecha_fin) {
    $sql .= " AND p.fecha_pago <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}

// Obtener el número total de resultados con los filtros aplicados
$total_query = "SELECT COUNT(*) AS total FROM pago p
    INNER JOIN miembro m ON p.id_miembro = m.id_miembro
    INNER JOIN usuario u ON m.id_usuario = u.id_usuario
    WHERE 1=1";

if ($busqueda) $total_query .= " AND u.nombre LIKE '%$busqueda%'";
if ($fecha_inicio) $total_query .= " AND p.fecha_pago >= '$fecha_inicio'";
if ($fecha_fin) $total_query .= " AND p.fecha_pago <= '$fecha_fin'";

$total_pagos_result = $conn->query($total_query);
$total_pagos = $total_pagos_result->fetch_assoc()['total'];
$total_pages = ceil($total_pagos / $limit);

// Orden y paginación
$sql .= " ORDER BY p.fecha_pago DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$pagos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2 class="section-title">Historial de Pagos</h2>

        <!-- Formulario de búsqueda -->
        <div class="form_container">
            <form method="GET" action="historial_pagos.php" class="form-inline">
                <div class="form-group">
                    <label for="busqueda">Nombre del Usuario:</label>
                    <input type="text" id="busqueda" name="busqueda" placeholder="Buscar por nombre..." value="<?php echo htmlspecialchars($busqueda); ?>" class="input-general">
                </div>

                <div class="form-group">
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" class="input-general">
                </div>

                <div class="form-group">
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" class="input-general">
                </div>

                <div class="form-group button-container">
                    <button type="submit" class="btn-general">Buscar</button>
                    <a href="historial_pagos.php" class="btn-general limpiar-busqueda">Limpiar</a>
                </div>
            </form>
        </div>

        <!-- Tabla con el historial de pagos -->
        <?php if (empty($pagos)): ?>
            <p class="mensaje-info">No se encontraron pagos con esos criterios.</p>
        <?php else: ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Nombre del Usuario</th>
                        <th>Email</th>
                        <th>Fecha de Pago</th>
                        <th>Monto</th>
                        <th>Método de Pago</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pago['usuario_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($pago['usuario_email']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></td>
                            <td>€<?php echo number_format($pago['monto'], 2); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($pago['metodo_pago'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="historial_pagos.php?page=<?php echo $page - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>" class="btn-general">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="historial_pagos.php?page=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>" class="btn-general <?php echo ($i === $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="historial_pagos.php?page=<?php echo $page + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&fecha_inicio=<?php echo urlencode($fecha_inicio); ?>&fecha_fin=<?php echo urlencode($fecha_fin); ?>" class="btn-general">Siguiente</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

</body>
<?php include '../includes/footer.php'; ?>

</html>
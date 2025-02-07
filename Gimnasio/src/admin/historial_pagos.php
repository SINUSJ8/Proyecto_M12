<?php

require_once('../includes/general.php');
require_once('../usuarios/user_functions.php');

verificarAdmin(); // Verificar que el usuario es administrador

$conn = obtenerConexion();
$title = "Historial de Pagos";

// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Número de resultados por página
$offset = ($page - 1) * $limit;

// Consultar el número total de pagos
$total_pagos_result = $conn->query("SELECT COUNT(*) AS total FROM pago");
$total_pagos = $total_pagos_result->fetch_assoc()['total'];
$total_pages = ceil($total_pagos / $limit);

// Obtener los pagos con la información solicitada
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
    ORDER BY p.fecha_pago DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$pagos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2 class="section-title">Historial de Pagos</h2>

        <!-- Tabla con el historial de pagos -->
        <?php if (empty($pagos)): ?>
            <p class="mensaje-info">No hay pagos registrados.</p>
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
                            <td><?php echo htmlspecialchars($pago['fecha_pago']); ?></td>
                            <td>€<?php echo number_format($pago['monto'], 2); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($pago['metodo_pago'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="historial_pagos.php?page=<?php echo $page - 1; ?>" class="btn-general">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="historial_pagos.php?page=<?php echo $i; ?>" class="btn-general <?php echo ($i === $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="historial_pagos.php?page=<?php echo $page + 1; ?>" class="btn-general">Siguiente</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>


</body>
<?php include '../includes/footer.php'; ?>

</html>
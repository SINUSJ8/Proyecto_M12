<?php
require_once('../admin/admin_functions.php');
verificarAdmin();
$conn = obtenerConexion();

// Consulta para obtener membresías con información del miembro y fechas
$sql = "
    SELECT 
        mm.id_miembro,
        u.nombre AS nombre_usuario,
        u.email,
        u.telefono,
        m.tipo AS tipo_membresia,
        m.precio,
        m.duracion,
        mm.fecha_inicio,
        mm.fecha_fin,
        mm.estado,
        mm.renovacion_automatica
    FROM 
        miembro_membresia mm
    INNER JOIN miembro mb ON mm.id_miembro = mb.id_miembro
    INNER JOIN usuario u ON mb.id_usuario = u.id_usuario
    INNER JOIN membresia m ON mm.id_membresia = m.id_membresia
    ORDER BY 
        CASE 
            WHEN m.tipo = 'anual' THEN 1
            WHEN m.tipo = 'mensual' THEN 2
            WHEN m.tipo = 'limitada' THEN 3
            ELSE 4
        END,
        m.precio ASC
";

$result = $conn->query($sql);
$membresias_miembros = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $membresias_miembros[] = $row;
    }
}
$title = "Membresías y Miembros";
include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2 class="section-title">Detalles de Membresías por Miembros</h2>
        <div class="form_container">
            <a href="crear_membresia.php" class="btn-general">Crear Nueva Membresía</a>
        </div>
        <?php if (!empty($membresias_miembros)): ?>
            <table id="tabla-membresias" class="styled-table">
                <thead>
                <tr>
                    <th onclick="ordenarTablaMe(0)" class="sortable">Nombre Miembro</th>
                    <th onclick="ordenarTablaMe(1)" class="sortable">Email</th>
                    <th onclick="ordenarTablaMe(2)" class="sortable">Teléfono</th>
                    <th onclick="ordenarTablaMe(3)" class="sortable">Membresía</th>
                    <th onclick="ordenarTablaMe(4)" class="sortable">Precio</th>
                    <th onclick="ordenarTablaMe(5)" class="sortable">Duración</th>
                    <th onclick="ordenarTablaMe(6)" class="sortable">Fecha Inicio</th>
                    <th onclick="ordenarTablaMe(7)" class="sortable">Fecha Fin</th>
                    <th onclick="ordenarTablaMe(8)" class="sortable">Estado</th>
                    <th onclick="ordenarTablaMe(9)" class="sortable">Renovación Automática</th>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($membresias_miembros as $dato): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dato['nombre_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($dato['email']); ?></td>
                            <td><?php echo htmlspecialchars(isset($dato['telefono']) ? $dato['telefono'] : 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($dato['tipo_membresia']); ?></td>
                            <td><?php echo htmlspecialchars($dato['precio']); ?> €</td>
                            <td><?php echo htmlspecialchars($dato['duracion']); ?> meses</td>
                            <td><?php echo htmlspecialchars($dato['fecha_inicio']); ?></td>
                            <td><?php echo htmlspecialchars($dato['fecha_fin']); ?></td>
                            <td><?php echo htmlspecialchars($dato['estado']); ?></td>
                            <td><?php echo $dato['renovacion_automatica'] ? 'Sí' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p>No hay membresías registradas para mostrar.</p>
        <?php endif; ?>
    </main>

    <?php
    include '../includes/footer.php';
    $conn->close();
    ?>
        <script src="../../assets/js/clases.js"></script>
</body>
</html>
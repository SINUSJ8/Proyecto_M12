<?php
require_once('../admin/admin_functions.php');
verificarAdmin();
$conn = obtenerConexion();

// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8; // Número de resultados por página
$offset = ($page - 1) * $limit;

// Capturar el término de búsqueda
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Consulta principal con paginación
$sql = "
    SELECT 
        mm.id AS id,
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
    WHERE 
        u.nombre LIKE ? OR
        m.tipo LIKE ?
    ORDER BY 
        CASE 
            WHEN m.tipo = 'anual' THEN 1
            WHEN m.tipo = 'mensual' THEN 2
            WHEN m.tipo = 'limitada' THEN 3
            ELSE 4
        END,
        m.precio ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$busquedaParam = '%' . $busqueda . '%';
$stmt->bind_param("ssii", $busquedaParam, $busquedaParam, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$membresias_miembros = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Consulta para obtener el número total de resultados
$sql_count = "
    SELECT 
        COUNT(*) as total
    FROM 
        miembro_membresia mm
    INNER JOIN miembro mb ON mm.id_miembro = mb.id_miembro
    INNER JOIN usuario u ON mb.id_usuario = u.id_usuario
    INNER JOIN membresia m ON mm.id_membresia = m.id_membresia
    WHERE 
        u.nombre LIKE ? OR
        m.tipo LIKE ?
";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("ss", $busquedaParam, $busquedaParam);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_rows = $result_count->fetch_assoc()['total'];
$stmt_count->close();

$total_pages = ceil($total_rows / $limit);

$title = "Membresías y Miembros";
include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2 class="section-title">Detalles de Membresías por Miembros</h2>

        <!-- Mostrar mensajes -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="mensaje-confirmacion">
                <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
            </div>
        <?php endif; ?>

        <!-- Formulario de búsqueda -->
        <div class="form_container">
            <form method="GET" action="membresias.php" style="display: inline;">
                <input type="text" name="busqueda" placeholder="Buscar membresía o usuario..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn-general">Buscar</button>
                <a href="membresias.php" class="btn-general limpiar-busqueda">Limpiar</a>
                <a href="crear_membresia.php" class="btn-general limpiar-busqueda">Crear Membresía</a>
            </form>
        </div>


        <!-- Tabla de datos -->
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($membresias_miembros as $dato): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dato['nombre_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($dato['email']); ?></td>
                            <td><?php echo htmlspecialchars(!empty($dato['telefono']) ? $dato['telefono'] : 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dato['tipo_membresia']); ?></td>
                            <td><?php echo htmlspecialchars($dato['precio']); ?> €</td>
                            <td><?php echo htmlspecialchars($dato['duracion']); ?> meses</td>
                            <td><?php echo htmlspecialchars($dato['fecha_inicio']); ?></td>
                            <td><?php echo htmlspecialchars($dato['fecha_fin']); ?></td>
                            <td><?php echo htmlspecialchars($dato['estado']); ?></td>
                            <td><?php echo $dato['renovacion_automatica'] ? 'Sí' : 'No'; ?></td>
                            <td>
                                <?php if ($dato['estado'] === 'activa'): ?>
                                    <!-- Mostrar solo el botón para desactivar -->
                                    <form action="membresia_acciones.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="id_membresia" value="<?php echo htmlspecialchars($dato['id']); ?>">
                                        <input type="hidden" name="accion" value="desactivar">
                                        <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                                        <button type="submit" class="btn-general btn-desactivar">Desactivar</button>
                                    </form>
                                <?php elseif ($dato['estado'] === 'expirada'): ?>
                                    <!-- Mostrar solo el botón para activar -->
                                    <form action="membresia_acciones.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="id_membresia" value="<?php echo htmlspecialchars($dato['id']); ?>">
                                        <input type="hidden" name="accion" value="activar">
                                        <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                                        <button type="submit" class="btn-general btn-activar">Activar</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Botón para eliminar siempre disponible -->
                                <form action="membresia_acciones.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id_membresia" value="<?php echo htmlspecialchars($dato['id']); ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                                    <button type="submit" class="delete-button" onclick="return confirm('¿Seguro que deseas eliminar esta membresía?');">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay membresías registradas para mostrar.</p>
        <?php endif; ?>
                </tbody>
            </table>
        <!-- Paginación -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="membresias.php?page=<?php echo $page - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="membresias.php?page=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>" class="btn-general <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="membresias.php?page=<?php echo $page + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>
    </main>
    <script src="../../assets/js/clases.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
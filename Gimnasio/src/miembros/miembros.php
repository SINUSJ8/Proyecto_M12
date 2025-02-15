<?php
require_once('../miembros/member_functions.php');

verificarAdmin();

$conn = obtenerConexion();

// Manejar acción de eliminación
// Manejar acción de eliminación
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['eliminar_usuario']) && isset($_POST['id_usuario'])) {
    $id_usuario = intval($_POST['id_usuario']); // Asegúrate de convertirlo a entero
    $resultado = eliminarMiembro($conn, $id_usuario);

    // Redirigir con un mensaje de confirmación o error
    if ($resultado['success']) {
        $mensaje = $resultado['message'];
        header("Location: miembros.php?mensaje=" . urlencode($mensaje));
        exit();
    } else {
        $mensaje = $resultado['message'];
        echo "<p class='error'>" . htmlspecialchars($mensaje) . "</p>"; // Muestra el error si ocurre
    }
}


// Capturar término de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8; // Número de resultados por página
$offset = ($page - 1) * $limit;

// Obtener el número total de miembros con el término de búsqueda
$total_miembros = obtenerTotalMiembros($conn, $busqueda);
$total_pages = ceil($total_miembros / $limit);

// Obtener los miembros con límite, offset y término de búsqueda

$miembros = obtenerMiembrosPaginados($conn, $limit, $offset, $busqueda);



$title = "Gestión de Miembros";
include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2 class="section-title">Gestión de Miembros</h2>

        <!-- Mostrar mensaje de confirmación si existe -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="mensaje-confirmacion">
                <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
            </div>
        <?php endif; ?>
        <div class="form_container">
            <form method="GET" action="miembros.php" class="search-form">
                <div class="form-inline">
                    <div class="input-container">
                        <input
                            type="text"
                            name="busqueda"
                            placeholder="Buscar miembro..."
                            value="<?php echo htmlspecialchars($busqueda ?? ''); ?>"
                            class="input-general">
                    </div>
                    <div class="button-container">
                        <button type="submit" class="btn-general">Buscar</button>
                        <a href="miembros.php" class="btn-general limpiar-busqueda">Limpiar</a>
                    </div>
                </div>
            </form>

        </div>

        <!-- Tabla con lista de miembros y acciones -->
        <table id="tabla-miembros" class="styled-table">
            <thead>
                <tr>
                    <th onclick="ordenarTablaMi(0, 'tabla-miembros')" class="sortable">Nombre</th>
                    <th onclick="ordenarTablaMi(1, 'tabla-miembros')" class="sortable">Email</th>
                    <th onclick="ordenarTablaMi(2, 'tabla-miembros')" class="sortable">Miembro desde</th>
                    <th onclick="ordenarTablaMi(3, 'tabla-miembros')" class="sortable">Tipo de Membresía</th>
                    <th onclick="ordenarTablaMi(4, 'tabla-miembros')" class="sortable">Entrenamientos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($miembros)): ?>
                    <?php foreach ($miembros as $miembro): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($miembro['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($miembro['email']); ?></td>
                            <td><?php echo date("d/m/Y", strtotime($miembro['fecha_registro'])); ?></td>
                            <td><?php echo htmlspecialchars($miembro['tipo_membresia'] ?? 'N/A'); ?></td>
                            <td>
                                <?php echo htmlspecialchars($miembro['entrenamientos'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="acciones">
                                <div class="button-container">
                                    <!-- Botón para editar -->
                                    <form action="edit_miembro.php" method="GET" style="display:inline;">
                                        <input type="hidden" name="id_usuario" value="<?php echo $miembro['id_usuario']; ?>">
                                        <button type="submit" class="btn-general edit-button" title="Modificar el perfil de este miembro">Modificar Perfil</button>
                                    </form>
                                    <!-- Botón para eliminar -->
                                    <form action="miembros.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="id_usuario" value="<?php echo $miembro['id_usuario']; ?>">
                                        <button type="submit" class="delete-button" name="eliminar_usuario" onclick="return confirm('¿Estás seguro de que deseas eliminar este miembro? Esta acción no se puede deshacer.')" title="Eliminar definitivamente este miembro">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No se encontraron miembros.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="miembros.php?page=<?php echo $page - 1; ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="miembros.php?page=<?php echo $i; ?>" class="btn-general <?php echo ($i === $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="miembros.php?page=<?php echo $page + 1; ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../../assets/js/clases.js"></script>
</body>

</html>
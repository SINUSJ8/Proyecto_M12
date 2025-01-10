<?php

require_once('../miembros/member_functions.php');
require_once('../usuarios/user_functions.php');

verificarAdmin();

$conn = obtenerConexion();

// Manejar acción de eliminación
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['eliminar_usuario']) && isset($_POST['id_usuario'])) {
    $id_usuario = $_POST['id_usuario'];
    $resultado = eliminarMiembro($conn, $id_usuario);

    // Redirigir con un mensaje de confirmación o error
    $mensaje = $resultado['message'];
    header("Location: miembros.php?mensaje=" . urlencode($mensaje));
    exit();
}

// Capturar el término de búsqueda y los parámetros de ordenamiento
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$orden_columna = isset($_GET['orden']) ? $_GET['orden'] : 'nombre';
$orden_direccion = isset($_GET['direccion']) ? $_GET['direccion'] : 'ASC';

// Obtener los miembros usando la función en member_functions.php
$miembros = obtenerMiembros($conn, $busqueda, $orden_columna, $orden_direccion);

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

        <!-- Formulario de búsqueda -->
        <div class="form_container">
            <form method="GET" action="miembros.php">
                <input type="text" name="busqueda" placeholder="Buscar miembro..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn-general">Buscar</button>
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
                <?php foreach ($miembros as $miembro): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($miembro['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($miembro['email']); ?></td>
                        <td><?php echo htmlspecialchars($miembro['fecha_registro']); ?></td>
                        <td><?php echo htmlspecialchars($miembro['tipo']); ?></td>
                        <td>
                            <?php
                            echo htmlspecialchars(isset($miembro['entrenamientos']) ? $miembro['entrenamientos'] : 'N/A', ENT_QUOTES, 'UTF-8');
                            ?>
                        </td>
                        <td class="acciones">
                            <div class="button-container">
                                <!-- Acción de editar -->
                                <form action="edit_miembro.php" method="GET" style="display:inline;">
                                    <input type="hidden" name="id_usuario" value="<?php echo $miembro['id_usuario']; ?>">
                                    <button type="submit" class="btn-general edit-button" name="editar_usuario" title="Modificar el perfil de este miembro">Modificar Perfil</button>
                                </form>                                                             
                                <!-- Acción de eliminar -->
                                <form action="miembros.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id_usuario" value="<?php echo $miembro['id_usuario']; ?>">
                                    <button type="submit" class="delete-button" name="eliminar_usuario" onclick="return confirm('¿Estás seguro de que deseas eliminar este miembro? Esta acción no se puede deshacer.')" title="Eliminar definitivamente este miembro">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>


    </main>

    <?php
    include '../includes/footer.php';
    $conn->close();
    ?>
    <script src="../../assets/js/clases.js"></script>
</body>

</html>
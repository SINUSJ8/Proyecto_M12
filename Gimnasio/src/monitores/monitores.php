<?php

require_once('../monitores/monitor_functions.php');
require_once('../usuarios/user_functions.php'); // Para la función verificarAdmin

verificarAdmin();

$conn = obtenerConexion();

// Manejar acción de eliminación
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['eliminar_usuario']) && isset($_POST['id_usuario'])) {
    $id_usuario = $_POST['id_usuario'];
    $resultado = eliminarMonitor($conn, $id_usuario);

    // Redirigir con un mensaje de confirmación o error
    $mensaje = $resultado['message'];
    header("Location: monitores.php?mensaje=" . urlencode($mensaje));
    exit();
}

// Capturar el término de búsqueda, especialidad, disponibilidad y los parámetros de ordenamiento
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$especialidad_filtro = isset($_GET['especialidad']) ? $_GET['especialidad'] : '';
$disponibilidad_filtro = isset($_GET['disponibilidad']) ? $_GET['disponibilidad'] : '';
$orden_columna = isset($_GET['orden']) ? $_GET['orden'] : 'nombre';
$orden_direccion = isset($_GET['direccion']) ? $_GET['direccion'] : 'ASC';

// Obtener los monitores usando la función en monitor_functions.php
$monitores = obtenerMonitores($conn, $busqueda, $orden_columna, $orden_direccion, $especialidad_filtro, $disponibilidad_filtro);

// Obtener la lista de especialidades
$especialidades = obtenerEspecialidades($conn);

$title = "Gestión de Monitores";
include '../admin/admin_header.php';

?>

<body>
    <main>
        <h2 class="section-title">Gestión de Monitores</h2>

        <!-- Mostrar mensaje de confirmación si existe -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="mensaje-confirmacion">
                <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
            </div>
        <?php endif; ?>

        <!-- Formulario de búsqueda -->
        <div class="form_container_monitor">
            <form method="GET" action="monitores.php" class="form-inline">
                <div class="form-group">
                    <label for="busqueda">Buscar Monitor:</label>
                    <input type="text" id="busqueda" name="busqueda" placeholder="Buscar monitor..." value="<?php echo htmlspecialchars($busqueda); ?>" class="input-general">
                </div>

                <div class="form-group">
                    <label for="especialidad">Especialidad:</label>
                    <select id="especialidad" name="especialidad" class="select-general">
                        <option value="">Todas las especialidades</option>
                        <?php foreach ($especialidades as $especialidad): ?>
                            <option value="<?php echo htmlspecialchars($especialidad['id_especialidad']); ?>" <?php echo ($especialidad_filtro == $especialidad['id_especialidad']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($especialidad['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="disponibilidad">Disponibilidad:</label>
                    <select id="disponibilidad" name="disponibilidad" class="select-general">
                        <option value="">Cualquiera</option>
                        <option value="Disponible" <?php echo ($disponibilidad_filtro === 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                        <option value="No disponible" <?php echo ($disponibilidad_filtro === 'No disponible') ? 'selected' : ''; ?>>No disponible</option>
                    </select>
                </div>

                <div class="form-group button-container">
                    <button type="submit" class="btn-general">Buscar</button>
                    <a href="monitores.php" class="btn-general limpiar-busqueda">Limpiar</a>
                    </div>
            </form>
        </div>



        <!-- Tabla con lista de monitores y acciones -->
        <section class="form_container_large">
            <table id="tabla-monitores" class="styled-table">
                <thead>
                    <tr>
                        <th onclick="ordenarTablaM(0, 'tabla-monitores')" class="sortable">Nombre</th>
                        <th onclick="ordenarTablaM(1, 'tabla-monitores')" class="sortable">Email</th>
                        <th onclick="ordenarTablaM(2, 'tabla-monitores')" class="sortable">Especialidades</th>
                        <th onclick="ordenarTablaM(3, 'tabla-monitores')" class="sortable">Experiencia</th>
                        <th onclick="ordenarTablaM(4, 'tabla-monitores')" class="sortable">Disponibilidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monitores as $monitor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($monitor['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($monitor['email']); ?></td>
                            <td><?php echo htmlspecialchars($monitor['especialidades']); ?></td>
                            <td><?php echo htmlspecialchars($monitor['experiencia']); ?> años</td>
                            <td><?php echo htmlspecialchars($monitor['disponibilidad']); ?></td>
                            <td class="acciones">
                                <form method="POST" action="monitores.php" onsubmit="return confirmarEliminacion();" style="margin-bottom: 5px;">
                                    <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($monitor['id_usuario']); ?>">
                                    <button type="submit" class="delete-button" name="eliminar_usuario">Eliminar</button>
                                </form>
                                <a href="edit_monitor.php?id_usuario=<?php echo htmlspecialchars($monitor['id_usuario']); ?>" class="btn-general edit-button">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <?php
    include '../includes/footer.php';
    $conn->close();
    ?>
    <script src="../../assets/js/clases.js"></script>
</body>


</html>
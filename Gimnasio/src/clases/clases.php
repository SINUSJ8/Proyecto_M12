<?php
require_once '../clases/class_functions.php';
require_once('../admin/admin_functions.php');
verificarAdmin();

$conn = obtenerConexion();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_clase'])) {
    $id_clase = intval($_POST['id_clase']);

    // Obtener los datos para enviar notificaciones antes de eliminar
    $miembros = obtenerMiembrosInscritos($conn, $id_clase);
    $monitor = obtenerMonitorDeClase($conn, $id_clase);

    // Enviar notificaciones a miembros
    foreach ($miembros as $miembro) {
        $mensaje = "La clase a la que estabas inscrito ha sido cancelada.";
        enviarNotificacion($conn, $miembro['id_usuario'], $mensaje);
    }

    // Enviar notificaciones al monitor
    if ($monitor) {
        $mensajeMonitor = "La clase que impartías ha sido cancelada.";
        enviarNotificacion($conn, $monitor['id_usuario'], $mensajeMonitor);
    }

    // Eliminar la clase
    $stmt = $conn->prepare("DELETE FROM clase WHERE id_clase = ?");
    $stmt->bind_param('i', $id_clase);
    $stmt->execute();
    $stmt->close();

    // Redirigir con un mensaje de confirmación
    header('Location: clases.php?mensaje=clase_eliminada');
    exit;
}

$filtros = [
    'nombre_clase' => isset($_GET['nombre_clase']) ? $_GET['nombre_clase'] : '',
    'nombre_monitor' => isset($_GET['nombre_monitor']) ? $_GET['nombre_monitor'] : '',
    'especialidad' => isset($_GET['especialidad']) ? $_GET['especialidad'] : '',
    'fecha' => isset($_GET['fecha']) ? $_GET['fecha'] : '',
];

$clases = obtenerClases($conn, $filtros);
$clases_json = json_encode($clases);

$title = "Listado de Clases";
include '../admin/admin_header.php';
?>

<?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'clase_eliminada'): ?>
    <p class="success-message">La clase se ha eliminado correctamente.</p>
<?php endif; ?>

<body>
    <main>
        <h2>Clases Existentes</h2>

        <!-- Formulario de búsqueda -->
        <form method="GET" action="clases.php" class="search-form">
            <input type="text" name="nombre_clase" placeholder="Nombre de la clase" value="<?= htmlspecialchars(isset($_GET['nombre_clase']) ? $_GET['nombre_clase'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="nombre_monitor" placeholder="Nombre del monitor" value="<?= htmlspecialchars(isset($_GET['nombre_monitor']) ? $_GET['nombre_monitor'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="especialidad" placeholder="Especialidad" value="<?= htmlspecialchars(isset($_GET['especialidad']) ? $_GET['especialidad'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="date" name="fecha" value="<?= htmlspecialchars(isset($_GET['fecha']) ? $_GET['fecha'] : '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Buscar</button>
            <button type="button" class="reset-button" onclick="limpiarFormulario()">Limpiar</button>
        </form>

        <!-- Botón para crear clase -->
        <div class="button-container">
            <a href="crear_clase.php" class="button">Crear Clase</a>
        </div>

        <!-- Tabla para mostrar clases -->
        <section class="form_container">
            <table id="tabla-clases" class="styled-table">
                <thead>
                    <tr>
                        <th onclick="ordenarTabla(0, 'tabla-clases')" class="sortable">Nombre</th>
                        <th onclick="ordenarTabla(1, 'tabla-clases')" class="sortable">Especialidad</th>
                        <th onclick="ordenarTabla(2, 'tabla-clases')" class="sortable">Monitor</th>
                        <th onclick="ordenarTabla(3, 'tabla-clases')" class="sortable">Fecha</th>
                        <th onclick="ordenarTabla(4, 'tabla-clases')" class="sortable">Horario</th>
                        <th onclick="ordenarTabla(5, 'tabla-clases')" class="sortable">Duración</th>
                        <th onclick="ordenarTabla(6, 'tabla-clases')" class="sortable">Capacidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clases as $clase): ?>
                        <tr>
                            <td><?= htmlspecialchars($clase['nombre']); ?></td>
                            <td><?= htmlspecialchars($clase['especialidad']); ?></td>
                            <td><?= htmlspecialchars($clase['monitor']); ?></td>
                            <td><?= htmlspecialchars($clase['fecha']); ?></td>
                            <td><?= htmlspecialchars($clase['horario']); ?></td>
                            <td><?= htmlspecialchars($clase['duracion']); ?> min</td>
                            <td><?= htmlspecialchars($clase['capacidad_maxima']); ?></td>
                            <td>
                                <form method="POST" action="clases.php" onsubmit="return confirmarEliminacion();">
                                    <input type="hidden" name="id_clase" value="<?= htmlspecialchars($clase['id_clase']); ?>">
                                    <button type="submit" class="delete-button">Eliminar</button>
                                </form>
                                <a href="editar_clase.php?id_clase=<?= htmlspecialchars($clase['id_clase']); ?>" class="edit-button">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <?php include '../includes/footer.php'; ?>


        <!-- Incluir el archivo de JavaScript externo -->
        <script src="../../assets/js/clases.js"></script>
        <script src="../../assets/js/calendario.js"></script>

        <script type="text/javascript">
            let clases = <?= $clases_json; ?>;
        </script>

    </main>
</body>
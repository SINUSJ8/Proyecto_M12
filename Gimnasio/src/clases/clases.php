<?php
require_once '../clases/class_functions.php';
require_once('../admin/admin_functions.php');
verificarAdmin();

$conn = obtenerConexion();

// Determinar tipo de clases a mostrar (actuales o anteriores)
$tipo = isset($_GET['tipo']) && $_GET['tipo'] === 'anteriores' ? 'anteriores' : 'actuales';

// Filtros de búsqueda
$filtros = [
    'nombre_clase' => isset($_GET['nombre_clase']) ? $_GET['nombre_clase'] : '',
    'nombre_monitor' => isset($_GET['nombre_monitor']) ? $_GET['nombre_monitor'] : '',
    'especialidad' => isset($_GET['especialidad']) ? $_GET['especialidad'] : '',
    'fecha' => isset($_GET['fecha']) ? $_GET['fecha'] : '',
];

// Obtener las clases según los filtros y el tipo de clase
$clases = obtenerClases($conn, $filtros, $tipo);
$clases_json = json_encode($clases);

$title = $tipo === 'anteriores' ? "Clases Anteriores" : "Listado de Clases";
include '../admin/admin_header.php';

// Eliminar clase y notificar
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
?>

<?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'clase_eliminada'): ?>
    <p class="success-message">La clase se ha eliminado correctamente.</p>
<?php endif; ?>

<body>
    <main>
        <h2><?= $title; ?></h2>

        <!-- Botones de navegación y creación -->
        <div class="button-container">
            <?php if ($tipo === 'anteriores'): ?>
                <a href="clases.php" class="button">Ver Clases Actuales</a>
            <?php else: ?>
                <a href="clases.php?tipo=anteriores" class="button">Ver Clases Anteriores</a>
            <?php endif; ?>
            <a href="crear_clase.php" class="button">Crear Clase</a>
        </div>

        <!-- Formulario de búsqueda -->
        <form method="GET" action="clases.php" class="search-form">
            <!-- Campo oculto para preservar el tipo -->
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo); ?>">

            <input type="text" name="nombre_clase" placeholder="Nombre de la clase" value="<?= htmlspecialchars($filtros['nombre_clase'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="nombre_monitor" placeholder="Nombre del monitor" value="<?= htmlspecialchars($filtros['nombre_monitor'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="especialidad" placeholder="Especialidad" value="<?= htmlspecialchars($filtros['especialidad'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="date" name="fecha" value="<?= htmlspecialchars($filtros['fecha'], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Buscar</button>
            <button type="button" class="reset-button" onclick="window.location.href='clases.php?tipo=<?= htmlspecialchars($tipo); ?>'">Limpiar</button>

        </form>


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
                        <?php
                        // Verificar si el monitor está disponible o no está asignado
                        $sinMonitor = empty($clase['monitor']) || $clase['monitor_disponible'] === 'no disponible';
                        ?>
                        <tr class="<?= $sinMonitor ? 'clase-sin-monitor' : ''; ?>">
                            <td><?= htmlspecialchars($clase['nombre']); ?></td>
                            <td><?= htmlspecialchars($clase['especialidad']); ?></td>
                            <td>
                                <?= htmlspecialchars($clase['monitor'] ?: 'No asignado'); ?>
                                <?php if ($clase['monitor_disponible'] === 'no disponible'): ?>
                                    <span class="texto-advertencia">(No disponible)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d-m-Y', strtotime($clase['fecha'])); ?></td>
                            <td><?= htmlspecialchars($clase['horario']); ?></td>
                            <td><?= htmlspecialchars($clase['duracion']); ?> min</td>
                            <td><?= htmlspecialchars($clase['capacidad_maxima']); ?></td>
                            <td class="acciones">
                                <div class="button-container">
                                    <!-- Ver Detalle -->
                                    <a href="detalle_clase.php?id_clase=<?= htmlspecialchars($clase['id_clase']); ?>" class="button">Ver Detalle</a>
                                    <!-- Editar -->
                                    <?php if ($tipo === 'actuales'): ?>
                                        <a href="editar_clase.php?id_clase=<?= htmlspecialchars($clase['id_clase']); ?>" class="button">Editar</a>
                                    <?php else: ?>
                                        <span class="btn-disabled">Editar no disponible</span>
                                    <?php endif; ?>
                                    <!-- Eliminar -->
                                    <?php if ($tipo === 'actuales'): ?>
                                        <form method="POST" action="clases.php">
                                            <input type="hidden" name="id_clase" value="<?= htmlspecialchars($clase['id_clase']); ?>">
                                            <button type="submit" class="delete-button">Eliminar</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="btn-disabled">Eliminar no disponible</span>
                                    <?php endif; ?>
                                </div>
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
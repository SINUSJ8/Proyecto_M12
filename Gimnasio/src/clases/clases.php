<?php
require_once '../clases/class_functions.php';
require_once('../admin/admin_functions.php');
require_once('../includes/notificaciones_functions.php');
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

// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8; // Resultados por página
$offset = ($page - 1) * $limit;

// Obtener el número total de clases
$total_clases = obtenerTotalClases($conn, $filtros, $tipo);
$total_pages = ceil($total_clases / $limit);

// Obtener las clases según los filtros, tipo y paginación
$clases = obtenerClasesPaginadas($conn, $filtros, $tipo, $limit, $offset);
$clases_json = json_encode($clases);

$title = $tipo === 'anteriores' ? "Clases Anteriores" : "Listado de Clases";
include '../admin/admin_header.php';

// Eliminar clase y evitar notificaciones si es una clase anterior
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_clase'])) {
    $id_clase = intval($_POST['id_clase']);
    $tipo = $_POST['tipo'] ?? 'actuales'; // Obtener el tipo de clase (actual o anterior)

    // Obtener el nombre de la clase antes de eliminarla
    $stmt = $conn->prepare("SELECT nombre FROM clase WHERE id_clase = ?");
    $stmt->bind_param('i', $id_clase);
    $stmt->execute();
    $stmt->bind_result($nombre_clase);
    $stmt->fetch();
    $stmt->close();

    if ($tipo === 'actuales') {
        // Obtener los datos para enviar notificaciones antes de eliminar
        $miembros = obtenerMiembrosInscritos($conn, $id_clase);
        $monitor = obtenerMonitorDeClase($conn, $id_clase);

        // Enviar notificaciones a miembros
        foreach ($miembros as $miembro) {
            $mensaje = "La clase '$nombre_clase' a la que estabas inscrito ha sido cancelada.";
            enviarNotificacion($conn, $miembro['id_usuario'], $mensaje);
        }

        // Enviar notificaciones al monitor
        if ($monitor) {
            $mensajeMonitor = "La clase '$nombre_clase' que impartías ha sido cancelada.";
            enviarNotificacion($conn, $monitor['id_usuario'], $mensajeMonitor);
        }
    }

    // Eliminar la clase (sin importar si es anterior o actual)
    $stmt = $conn->prepare("DELETE FROM clase WHERE id_clase = ?");
    $stmt->bind_param('i', $id_clase);
    $stmt->execute();
    $stmt->close();

    // Redirigir con un mensaje de confirmación
    header("Location: clases.php?tipo={$tipo}&mensaje=clase_eliminada");
    exit;
}

?>

<body>
    <main>
        <h2 class="section-title"><?= $title; ?></h2>

        <!-- Botones de navegación y creación -->
        <div class="button-container">
            <?php if ($tipo === 'anteriores'): ?>
                <a href="clases.php" class="btn-general">Ver Clases Actuales</a>
            <?php else: ?>
                <a href="clases.php?tipo=anteriores" class="btn-general">Ver Clases Anteriores</a>
            <?php endif; ?>
            <a href="crear_clase.php" class="btn-general">Crear Clase</a>
            <a href="buscar_clase.php" class="btn-general">Buscar Clases</a>
        </div>

        <!-- Mostrar mensaje de confirmación si existe -->
        <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'clase_eliminada'): ?>
            <p class="mensaje-confirmacion">La clase se ha eliminado correctamente.</p>
        <?php endif; ?>

        <!-- Tabla para mostrar clases -->
        <table id="tabla-clases" class="styled-table <?= $tipo === 'anteriores' ? 'clases-anteriores' : ''; ?>">

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
                    $sinMonitor = empty($clase['monitor']) || $clase['monitor_disponible'] === 'no disponible';
                    $num_inscritos = obtenerNumeroInscritos($conn, $clase['id_clase']); // Función para contar inscritos
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
                        <td><?= $num_inscritos; ?> / <?= htmlspecialchars($clase['capacidad_maxima']); ?></td>
                        <td class="acciones">
                            <div class="button-container">
                                <a href="detalle_clase.php?id_clase=<?= htmlspecialchars($clase['id_clase']); ?>" class="btn-general">Ver Detalle</a>


                                <?php if ($tipo === 'actuales'): ?>
                                    <a href="editar_clase.php?id_clase=<?= htmlspecialchars($clase['id_clase']); ?>" class="btn-general edit-button">Editar</a>
                                <?php endif; ?>

                                <form method="POST" action="clases.php">
                                    <input type="hidden" name="id_clase" value="<?= htmlspecialchars($clase['id_clase']); ?>">
                                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo); ?>"> <!-- Agregar tipo -->
                                    <button type="submit" class="delete-button">Eliminar</button>
                                </form>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>

        <!-- Paginación -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="clases.php?page=<?= $page - 1; ?>&tipo=<?= $tipo; ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="clases.php?page=<?= $i; ?>&tipo=<?= $tipo; ?>" class="btn-general <?= $i === $page ? 'active' : ''; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="clases.php?page=<?= $page + 1; ?>&tipo=<?= $tipo; ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>
        <!-- Scripts -->
        <script src="../../assets/js/clases.js"></script>
        <script src="../../assets/js/calendario.js"></script>
        <script type="text/javascript">
            let clases = <?= $clases_json; ?>;
        </script>
    </main>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>
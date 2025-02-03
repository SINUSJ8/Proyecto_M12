<?php
require_once '../clases/class_functions.php';
require_once('../admin/admin_functions.php');
verificarAdmin();

$conn = obtenerConexion();

// Manejo de eliminación de clase
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
    header('Location: buscar_clase.php?mensaje=clase_eliminada');
    exit;
}

// Obtener monitores y especialidades para los desplegables
$especialidades = $conn->query("SELECT nombre FROM especialidad")->fetch_all(MYSQLI_ASSOC);
$monitores = $conn->query("
    SELECT u.nombre 
    FROM monitor m
    INNER JOIN usuario u ON m.id_usuario = u.id_usuario
")->fetch_all(MYSQLI_ASSOC);

// Filtros para la búsqueda
$filtros = [
    'nombre_clase' => isset($_GET['nombre_clase']) ? $_GET['nombre_clase'] : '',
    'nombre_monitor' => isset($_GET['nombre_monitor']) ? $_GET['nombre_monitor'] : '',
    'especialidad' => isset($_GET['especialidad']) ? $_GET['especialidad'] : '',
    'fecha' => isset($_GET['fecha']) ? $_GET['fecha'] : '',
];

// Si se realiza una búsqueda, obtener las clases
$clases = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty(array_filter($filtros))) {
    $clases = obtenerClases($conn, $filtros, 'actuales');
}

$title = "Buscar Clase";
include '../admin/admin_header.php';
?>


<body>
    <main>
        <h2 class="section-title">Buscar Clase</h2>
        <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'clase_eliminada'): ?>
            <p class="mensaje-confirmacion">La clase se ha eliminado correctamente.</p>
        <?php endif; ?>

        <!-- Formulario de búsqueda -->
        <form method="GET" action="buscar_clase.php" class="form_container">
            <div class="form-group">
                <label for="nombre_clase">Nombre de la Clase:</label>
                <input type="text" id="nombre_clase" name="nombre_clase" value="<?= htmlspecialchars($filtros['nombre_clase'], ENT_QUOTES); ?>" class="input-general">
            </div>

            <div class="form-group">
                <label for="nombre_monitor">Monitor:</label>
                <select id="nombre_monitor" name="nombre_monitor" class="input-general">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($monitores as $monitor): ?>
                        <option value="<?= htmlspecialchars($monitor['nombre'], ENT_QUOTES); ?>" <?= $filtros['nombre_monitor'] === $monitor['nombre'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($monitor['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="especialidad">Especialidad:</label>
                <select id="especialidad" name="especialidad" class="input-general">
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($especialidades as $especialidad): ?>
                        <option value="<?= htmlspecialchars($especialidad['nombre'], ENT_QUOTES); ?>" <?= $filtros['especialidad'] === $especialidad['nombre'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($especialidad['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($filtros['fecha'], ENT_QUOTES); ?>" class="input-general">
            </div>

            <div class="button-container">
                <button type="submit" class="btn-general">Buscar</button>
                <a href="buscar_clase.php" class="btn-general">Limpiar</a>
            </div>
            <div class="button-container">
                <a href="clases.php" class="btn-general btn-secondary">Volver a Clases</a>
            </div>
        </form>

        <!-- Resultados de la búsqueda -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($clases)): ?>
            <p class="mensaje-info">No se encontraron clases con los filtros aplicados.</p>
        <?php elseif (!empty($clases)): ?>
            <table id="tabla-clases" class="styled-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Especialidad</th>
                        <th>Monitor</th>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Duración</th>
                        <th>Capacidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clases as $clase): ?>
                        <?php
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
                                    <a href="detalle_clase.php?id_clase=<?= htmlspecialchars($clase['id_clase']) . '&' . http_build_query($filtros); ?>" class="btn-general">Ver Detalle</a>
                                    <a href="editar_clase.php?id_clase=<?= htmlspecialchars($clase['id_clase']); ?>" class="btn-general edit-button">Editar</a>
                                    <form method="POST" action="buscar_clase.php">
                                        <input type="hidden" name="id_clase" value="<?= htmlspecialchars($clase['id_clase']); ?>">
                                        <button type="submit" class="delete-button">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </main>

    <?php include '../includes/footer.php'; ?>
</body>
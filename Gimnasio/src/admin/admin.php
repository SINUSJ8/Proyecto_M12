<?php
require_once('admin_functions.php');
require_once('../miembros/member_functions.php'); //Para usar la función marcarNotificacionesComoLeidas
verificarAdmin();

$conn = obtenerConexion();

$title = "Panel de Administrador";
include 'admin_header.php';

$id_usuario = $_SESSION['id_usuario']; // ID del administrador actual

// Determinar si mostrar todas las notificaciones o solo las nuevas
$mostrar_todas = isset($_GET['ver']) && $_GET['ver'] === 'anteriores';

// Consultas para obtener datos del panel de administrador
$num_miembros = obtenerConteoMiembros($conn);
$num_clases = obtenerConteoClases($conn);
$num_monitores = obtenerConteoMonitores($conn);

// Obtener notificaciones según el parámetro GET
$notificaciones = obtenerNotificaciones($conn, $id_usuario, 10, !$mostrar_todas);
// Si no está viendo todas, marcar las nuevas como leídas
if (!$mostrar_todas) {
    marcarNotificacionesComoLeidas($conn, $id_usuario);
}
?>

<body>
    <main id="admin-panel">
        <section id="bienvenida">
            <h2>Resumen del Gimnasio</h2>
            <p>Miembros registrados: <?php echo $num_miembros; ?></p>
            <p>Clases disponibles: <?php echo $num_clases; ?></p>
            <p>Monitores: <?php echo $num_monitores; ?></p>
        </section>

        <section id="notificaciones">
            <h2>Mis Notificaciones</h2>

            <?php if ($mostrar_todas): ?>
                <a href="admin.php" class="btn-general">Ver solo nuevas</a>
            <?php else: ?>
                <a href="admin.php?ver=anteriores" class="btn-general">Ver todas las notificaciones</a>
            <?php endif; ?>

            <?php if ($notificaciones && count($notificaciones) > 0): ?>
                <ul>
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <li class="<?php echo $notificacion['leida'] ? 'notificacion-leida' : 'notificacion-nueva'; ?>">
                            <?php echo htmlspecialchars($notificacion['mensaje']); ?> -
                            <em><?php echo htmlspecialchars($notificacion['fecha']); ?></em>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No hay notificaciones pendientes.</p>
            <?php endif; ?>
        </section>
    </main>

    <?php
    // Cerrar conexión a la base de datos
    $conn->close();
    ?>

    <?php include '../includes/footer.php'; ?>
</body>
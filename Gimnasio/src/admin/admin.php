<?php
require_once('admin_functions.php');
require_once('../miembros/member_functions.php'); // Para usar la función marcarNotificacionesComoLeidas
verificarAdmin();

$conn = obtenerConexion();

$title = "Panel de Administrador";
include 'admin_header.php';

$id_usuario = $_SESSION['id_usuario']; // ID del administrador actual

// Determinar si mostrar todas las notificaciones o solo las nuevas
$mostrar_todas = isset($_GET['ver']) && $_GET['ver'] === 'anteriores';

// Consultas principales para datos del panel de administrador
$num_miembros = obtenerConteoMiembros($conn);
$num_clases = obtenerConteoClases($conn);
$num_monitores = obtenerConteoMonitores($conn);

// Datos adicionales
$altas_recientes = obtenerAltasRecientes($conn, 5); // Últimos 5 miembros
$altas_mes = obtenerAltasDelMes($conn); // Altas del mes actual
$clases_populares = obtenerClasesMasPopulares($conn, 3); // Top 3 clases
$clase_max_miembros = obtenerClaseMaxMiembros($conn);
$ingresos_totales = obtenerIngresosTotales($conn);
$ingresos_mes = obtenerIngresosDelMes($conn);
$monitores_activos = obtenerMonitoresActivos($conn);

// Obtener notificaciones
$notificaciones = obtenerNotificaciones($conn, $id_usuario, 10, !$mostrar_todas);
// Si no está viendo todas, marcar las nuevas como leídas
if (!$mostrar_todas) {
    marcarNotificacionesComoLeidas($conn, $id_usuario);
}

$conn->close();
?>

<body>
    <main id="admin-panel">
        <section id="resumen" class="admin_container">
            <h2 class="section-title">Resumen del Gimnasio</h2>
            <p>Miembros registrados: <strong><?php echo $num_miembros; ?></strong></p>
            <p>Clases disponibles: <strong><?php echo $num_clases; ?></strong></p>
            <p>Monitores activos: <strong><?php echo count($monitores_activos); ?></strong></p>
        </section>

        <section id="altas" class="admin_container">
            <h2 class="section-title">Altas de Miembros</h2>
            <p>Altas este mes: <strong><?php echo $altas_mes; ?></strong></p>
            <h3>Últimos Miembros Registrados</h3>
            <ul style="list-style-type: none; padding: 0; text-align: center;">
                <?php foreach ($altas_recientes as $alta): ?>
                    <li><?php echo htmlspecialchars($alta['nombre']); ?> (<?php echo htmlspecialchars($alta['fecha_registro']); ?>)</li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section id="clases" class="admin_container">
            <h2 class="section-title">Clases</h2>
            <h3>Clases Más Populares</h3>
            <ul style="list-style-type: none; padding: 0; text-align: center;">
                <?php foreach ($clases_populares as $clase): ?>
                    <li><?php echo htmlspecialchars($clase['nombre']); ?> - <?php echo $clase['inscripciones']; ?> inscripciones</li>
                <?php endforeach; ?>
            </ul>
            <h3>Clase con Más Miembros</h3>
            <p style="text-align: center;">
                <?php echo htmlspecialchars($clase_max_miembros['nombre']); ?> con <?php echo $clase_max_miembros['miembros']; ?> miembros inscritos.
            </p>
        </section>

        <section id="ingresos" class="admin_container">
            <h2 class="section-title">Ingresos</h2>
            <p>Ingresos Totales: €<?php echo number_format($ingresos_totales, 2); ?></p>
            <p>Ingresos Este Mes: €<?php echo number_format($ingresos_mes, 2); ?></p>
        </section>

        <section id="monitores" class="admin_container">
            <h2 class="section-title">Monitores Activos</h2>
            <ul style="list-style-type: none; padding: 0; text-align: center;">
                <?php foreach ($monitores_activos as $monitor): ?>
                    <li><?php echo htmlspecialchars($monitor['nombre']); ?> - <?php echo htmlspecialchars($monitor['disponibilidad']); ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section id="notificaciones" class="admin_container">
            <h2 class="section-title">Mis Notificaciones</h2>
            <?php if ($mostrar_todas): ?>
                <a href="admin.php" class="btn-general">Ver solo nuevas</a>
            <?php else: ?>
                <a href="admin.php?ver=anteriores" class="btn-general">Ver todas las notificaciones</a>
            <?php endif; ?>

            <?php if ($notificaciones && count($notificaciones) > 0): ?>
                <ul style="list-style-type: none; padding: 0; text-align: center;">
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <li class="<?php echo $notificacion['leida'] ? 'notificacion-leida' : 'notificacion-nueva'; ?>" style="margin: 10px 0;">
                            <?php echo htmlspecialchars($notificacion['mensaje']); ?> -
                            <em><?php echo htmlspecialchars($notificacion['fecha']); ?></em>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="text-align: center;">No hay notificaciones pendientes.</p>
            <?php endif; ?>
        </section>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
<?php
require_once('../includes/general.php');
require_once('../miembros/member_functions.php');

// Verificar que el usuario es monitor
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'monitor') {
    header("Location: ../index.php?error=Acceso+denegado");
    exit();
}

$conn = obtenerConexion();
$title = "Mis Notificaciones";
$id_usuario = $_SESSION['id_usuario'];

// Obtener las notificaciones del monitor
$notificaciones = obtenerNotificacionesPorUsuario($conn, $id_usuario);

// Marcar las notificaciones como leídas (solo después de obtenerlas)
marcarNotificacionesComoLeidas($conn, $id_usuario);

include 'monitores_header.php'; // Usar un archivo de cabecera específico para monitores
?>

<main class="form_container">
    <h2 class="section-title">Mis Notificaciones</h2>

    <?php if (empty($notificaciones)): ?>
        <p class="mensaje-info">No tienes notificaciones.</p>
    <?php else: ?>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notificaciones as $notificacion): ?>
                    <tr class="<?php echo $notificacion['leida'] ? 'notificacion-leida' : 'notificacion-nueva'; ?>">
                        <td><?php echo htmlspecialchars($notificacion['mensaje']); ?></td>
                        <td><?php echo htmlspecialchars($notificacion['fecha']); ?></td>
                        <td><?php echo $notificacion['leida'] ? 'Leída' : 'Nueva'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
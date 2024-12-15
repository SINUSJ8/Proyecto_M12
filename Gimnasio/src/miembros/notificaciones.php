<?php
require_once('../includes/general.php');
require_once('../miembros/member_functions.php');



// Verifica que el mimebro ha iniciado sesión 
if (!isset($_SESSION['id_usuario'])) { 
    header("Location: index.php?error=Acceso+denegado"); 
    exit(); 
}

$id_usuario = $_SESSION['id_usuario'];

$conn = obtenerConexion();


// Obtener las notificaciones del miembro iniciado
$notificaciones = [];
$result = $conn->query("
    SELECT n.id_notificacion, u.nombre, n.mensaje, n.fecha, n.leida 
    FROM notificacion n
    INNER JOIN usuario u ON n.id_usuario = u.id_usuario
    WHERE n.id_usuario = $id_usuario
    ORDER BY n.fecha DESC
");

if ($result) {
    $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
}

?>

<main>
<link rel="stylesheet" href="../../assets/css/estilos.css">
    <!-- Lista de notificaciones recibidas -->
    <section>
        <h3>Notificaciones recibidas</h3>
        <?php if (empty($notificaciones)): ?>
            <p>No se han recibido notificaciones.</p>
        <?php else: ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Leída</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($notificacion['id_notificacion']); ?></td>
                            <td><?php echo htmlspecialchars($notificacion['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($notificacion['mensaje']); ?></td>
                            <td><?php echo htmlspecialchars($notificacion['fecha']); ?></td>
                            <td><?php echo $notificacion['leida'] ? 'Sí' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
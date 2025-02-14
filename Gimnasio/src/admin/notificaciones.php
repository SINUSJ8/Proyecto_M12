<?php
require_once('../includes/general.php');
require_once('../miembros/member_functions.php');
require_once('../admin/admin_functions.php');
require_once('../includes/notificaciones_functions.php');

// Verificar que el usuario sea administrador
verificarAdmin();

$conn = obtenerConexion();
$title = "Gestión de Notificaciones";

// Manejar solicitudes AJAX para búsqueda dinámica
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo "Acceso no autorizado";
        exit;
    }
    // Realizar la búsqueda de usuarios
    $termino = $_GET['q'];
    $usuarios = buscarUsuariosPorTermino($conn, $termino);
    foreach ($usuarios as $usuario) {
        echo '<option value="' . htmlspecialchars($usuario['id_usuario']) . '">' .
            htmlspecialchars($usuario['nombre']) . ' (' . htmlspecialchars($usuario['email']) . ')</option>';
    }
    exit;
}

// Manejo de envío de notificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {
    $mensaje = $_POST['mensaje'];
    $destinatario = $_POST['destinatario'] ?? null;

    if ($mensaje && $destinatario) {
        $usuarios = [];

        if ($destinatario === 'todos') {
            $usuarios = obtenerUsuariosSinFiltro($conn);
        } elseif ($destinatario === 'grupo') {
            $grupo = $_POST['grupo'] ?? null;
            if ($grupo) {
                switch ($grupo) {
                    case 'usuarios':
                        $usuarios = obtenerUsuariosPorRol($conn, 'usuario');
                        break;
                    case 'miembros':
                        $usuarios = obtenerUsuariosPorRol($conn, 'miembro');
                        break;
                    case 'monitores':
                        $usuarios = obtenerUsuariosPorRol($conn, 'monitor');
                        break;
                    case 'administradores':
                        $usuarios = obtenerUsuariosPorRol($conn, 'admin');
                        break;
                }
            }
        } elseif ($destinatario === 'usuario') {
            $id_usuario = $_POST['id_usuario'] ?? null;
            if ($id_usuario) {
                $usuarios = obtenerUsuarioPorId($conn, $id_usuario);
            }
        }
        // Enviar notificaciones a los usuarios seleccionados
        if (!empty($usuarios)) {
            $stmt = $conn->prepare("INSERT INTO notificacion (id_usuario, mensaje) VALUES (?, ?)");
            foreach ($usuarios as $usuario) {
                $stmt->bind_param("is", $usuario['id_usuario'], $mensaje);
                $stmt->execute();
            }
            $stmt->close();
            $success = "Notificaciones enviadas correctamente.";
        } else {
            $error = "No se encontraron destinatarios válidos.";
        }
    } else {
        $error = "Debes proporcionar un mensaje y un destinatario válido.";
    }
}

// Manejo de ocultar/restaurar/eliminar notificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id_notificacion = $_POST['id_notificacion'] ?? null;
    $id_usuario = $_SESSION['id_usuario'];

    if ($id_notificacion) {
        if ($_POST['accion'] === 'ocultar') {
            ocultarNotificacion($conn, $id_notificacion, $id_usuario);
        } elseif ($_POST['accion'] === 'restaurar') {
            restaurarNotificacion($conn, $id_notificacion, $id_usuario);
        } elseif ($_POST['accion'] === 'eliminar') {
            $stmt = $conn->prepare("DELETE FROM notificacion WHERE id_notificacion = ?");
            $stmt->bind_param("i", $id_notificacion);
            $stmt->execute();
            $stmt->close();
            $success = "Notificación eliminada correctamente.";
        }
    }
}

// Paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_query = $conn->query("
    SELECT COUNT(*) AS total 
    FROM notificacion n
    LEFT JOIN notificacion_oculta no ON n.id_notificacion = no.id_notificacion 
    AND no.id_usuario = {$_SESSION['id_usuario']}
    WHERE n.id_usuario = {$_SESSION['id_usuario']} 
    AND no.id_notificacion IS NULL
");

$total_result = $total_query->fetch_assoc();
$total_notificaciones = $total_result['total'];
$total_pages = ceil($total_notificaciones / $limit);


$result = $conn->query("
    SELECT n.id_notificacion, u.nombre, n.mensaje, n.fecha, n.leida 
FROM notificacion n
INNER JOIN usuario u ON n.id_usuario = u.id_usuario
LEFT JOIN notificacion_oculta no ON n.id_notificacion = no.id_notificacion 
    AND no.id_usuario = {$_SESSION['id_usuario']}
WHERE n.id_usuario = {$_SESSION['id_usuario']} 
AND no.id_notificacion IS NULL
ORDER BY n.fecha DESC
LIMIT $limit OFFSET $offset

");

$notificaciones = $result->fetch_all(MYSQLI_ASSOC);
// Marcar como leídas todas las notificaciones visibles del usuario
$stmt = $conn->prepare("UPDATE notificacion n
    LEFT JOIN notificacion_oculta no ON n.id_notificacion = no.id_notificacion 
    AND no.id_usuario = ?
    SET n.leida = 1 
    WHERE n.id_usuario = ? AND no.id_notificacion IS NULL");

$stmt->bind_param("ii", $_SESSION['id_usuario'], $_SESSION['id_usuario']);
$stmt->execute();
$stmt->close();
include 'admin_header.php';
?>

<main>
    <h2 class="section-title">Gestión de Notificaciones</h2>
    <!-- Mostrar mensajes de éxito o error -->
    <?php if (isset($success)): ?>
        <div class="mensaje-confirmacion"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <!-- Formulario para enviar notificaciones -->
    <section>
        <h3>Enviar Notificación</h3>
        <form method="POST" action="notificaciones.php" class="form_container">
            <label for="destinatario">Seleccionar Destinatario:</label>
            <select name="destinatario" id="destinatario" required class="select-general" onchange="toggleDestinatario()">
                <option value="">-- Selecciona un destinatario --</option>
                <option value="todos">Todos los Usuarios</option>
                <option value="grupo">Grupo Específico</option>
                <option value="usuario">Usuario Específico</option>
            </select>

            <div id="grupo_destinatario" style="display: none;">
                <label for="grupo">Seleccionar Grupo:</label>
                <select name="grupo" id="grupo" class="select-general">
                    <option value="usuarios">Usuarios</option>
                    <option value="miembros">Miembros</option>
                    <option value="monitores">Monitores</option>
                    <option value="administradores">Administradores</option>
                </select>
            </div>

            <div id="usuario_destinatario" style="display: none;">
                <label for="buscar_usuario">Buscar Usuario:</label>
                <input type="text" id="buscar_usuario" placeholder="Escribe para buscar usuarios..." class="input-general" onkeyup="buscarUsuario(this.value)">
                <label for="id_usuario">Seleccionar Usuario:</label>
                <select name="id_usuario" id="id_usuario" class="select-general">
                    <option value="">-- Selecciona un usuario --</option>
                </select>
            </div>
            <div class="notificacion-mensaje">
                <textarea name="mensaje" required class="input-general" placeholder="Tu mensaje"></textarea>
                <button type="submit" class="btn-general">Enviar</button>
            </div>
        </form>
    </section>
    <a href="notificaciones_enviadas.php" class="btn-general">Ver Notificaciones Enviadas</a>
    <!-- Mostrar notificaciones dirigidas al administrador -->
    <section>
        <h3>Mis Notificaciones</h3>
        <?php if (empty($notificaciones)): ?>
            <p class="mensaje-info">No tienes notificaciones.</p>
        <?php else: ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <tr class="<?php echo $notificacion['leida'] ? 'notificacion-leida' : 'notificacion-nueva'; ?>">
                            <td><?php echo htmlspecialchars($notificacion['mensaje']); ?></td>
                            <td><?php echo htmlspecialchars($notificacion['fecha']); ?></td>
                            <td><?php echo $notificacion['leida'] ? 'Leída' : 'Nueva'; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_notificacion" value="<?php echo $notificacion['id_notificacion']; ?>">
                                    <button type="submit" name="accion" value="ocultar" class="btn-general">Ocultar</button>
                                    <button type="submit" name="accion" value="eliminar" class="delete-button">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="notificaciones.php?page=<?php echo $page - 1; ?>" " class=" btn-general">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="notificaciones.php?page=<?php echo $i; ?>" " class=" btn-general"<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="notificaciones.php?page=<?php echo $page + 1; ?>"" class=" btn-general">Siguiente</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<script src="../../assets/js/alertas.js"></script>
<?php include '../includes/footer.php'; ?>
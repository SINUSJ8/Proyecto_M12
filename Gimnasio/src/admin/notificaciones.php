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
        // Enviar notificaciones usando la función enviarNotificacion()
        if (!empty($usuarios)) {
            foreach ($usuarios as $usuario) {
                enviarNotificacion($conn, $usuario['id_usuario'], $mensaje);
            }
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
    <h2 class="section-title" title="Gestión de notificaciones enviadas y recibidas">Gestión de Notificaciones</h2>

    <!-- Mostrar mensajes de éxito o error -->
    <?php if (isset($success)): ?>
        <div class="mensaje-confirmacion" title="Notificación enviada con éxito">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="mensaje-error" title="Error al enviar la notificación">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Formulario para enviar notificaciones -->
    <section>
        <h3 title="Enviar una nueva notificación">Enviar Notificación</h3>
        <form method="POST" action="notificaciones.php" class="form_container" title="Formulario para enviar notificaciones">
            <label for="destinatario" title="Selecciona quién recibirá la notificación">Seleccionar Destinatario:</label>
            <select name="destinatario" id="destinatario" required class="select-general" title="Elige el tipo de destinatario" onchange="toggleDestinatario()">
                <option value="">-- Selecciona un destinatario --</option>
                <option value="todos">Todos los Usuarios</option>
                <option value="grupo">Grupo Específico</option>
                <option value="usuario">Usuario Específico</option>
            </select>

            <div id="grupo_destinatario" style="display: none;">
                <label for="grupo" title="Selecciona un grupo de usuarios">Seleccionar Grupo:</label>
                <select name="grupo" id="grupo" class="select-general" title="Elige un grupo de usuarios">
                    <option value="usuarios">Usuarios</option>
                    <option value="miembros">Miembros</option>
                    <option value="monitores">Monitores</option>
                    <option value="administradores">Administradores</option>
                </select>
            </div>

            <div id="usuario_destinatario" style="display: none;">
                <label for="buscar_usuario" title="Escribe un nombre o email para buscar un usuario">Buscar Usuario:</label>
                <input type="text" id="buscar_usuario" placeholder="Escribe para buscar usuarios..." class="input-general" title="Busca un usuario específico" onkeyup="buscarUsuario(this.value)">
                <label for="id_usuario" title="Selecciona un usuario de la lista">Seleccionar Usuario:</label>
                <select name="id_usuario" id="id_usuario" class="select-general" title="Elige un usuario de la lista">
                    <option value="">-- Selecciona un usuario --</option>
                </select>
            </div>

            <div class="notificacion-mensaje">
                <textarea name="mensaje" required class="input-general" placeholder="Tu mensaje" title="Escribe el contenido de la notificación"></textarea>
                <button type="submit" class="btn-general" title="Enviar la notificación">Enviar</button>
            </div>
        </form>
    </section>

    <a href="notificaciones_enviadas.php" class="btn-general" title="Ver todas las notificaciones enviadas">Ver Notificaciones Enviadas</a>

    <!-- Mostrar notificaciones dirigidas al administrador -->
    <section>
        <h3 title="Lista de notificaciones recibidas">Mis Notificaciones</h3>
        <?php if (empty($notificaciones)): ?>
            <p class="mensaje-info" title="No tienes notificaciones nuevas">No tienes notificaciones.</p>
        <?php else: ?>
            <table class="styled-table" title="Tabla con las notificaciones recibidas">
                <thead>
                    <tr>
                        <th title="Contenido de la notificación">Mensaje</th>
                        <th title="Fecha en que se recibió la notificación">Fecha</th>
                        <th title="Estado de la notificación">Estado</th>
                        <th title="Opciones disponibles">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <tr class="<?php echo $notificacion['leida'] ? 'notificacion-leida' : 'notificacion-nueva'; ?>">
                            <td title="<?php echo htmlspecialchars($notificacion['mensaje']); ?>">
                                <?php echo htmlspecialchars(substr($notificacion['mensaje'], 0, 100)) . '...'; ?>
                            </td>
                            <td title="Fecha de la notificación">
                                <?php echo date("d/m/Y", strtotime($notificacion['fecha'])); ?>
                            </td>
                            <td title="Estado de la notificación">
                                <?php echo $notificacion['leida'] ? 'Leída' : 'Nueva'; ?>
                            </td>
                            <td title="Acciones disponibles para esta notificación">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_notificacion" value="<?php echo $notificacion['id_notificacion']; ?>">
                                    <button type="submit" name="accion" value="ocultar" class="btn-general" title="Ocultar esta notificación">Ocultar</button>
                                    <button type="submit" name="accion" value="eliminar" class="delete-button" title="Eliminar esta notificación">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination" title="Paginación de notificaciones">
                <?php if ($page > 1): ?>
                    <a href="notificaciones.php?page=<?php echo $page - 1; ?>" class="btn-general" title="Ir a la página anterior">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="notificaciones.php?page=<?php echo $i; ?>" class="btn-general" title="Ir a la página <?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="notificaciones.php?page=<?php echo $page + 1; ?>" class="btn-general" title="Ir a la siguiente página">Siguiente</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<script src="../../assets/js/alertas.js"></script>
<?php include '../includes/footer.php'; ?>
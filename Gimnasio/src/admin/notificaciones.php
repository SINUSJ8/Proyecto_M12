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
        http_response_code(403); // Respuesta prohibida
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

// Manejo del formulario de creación de notificaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = $_POST['mensaje'] ?? null;
    $destinatario = $_POST['destinatario'] ?? null;

    if ($mensaje && $destinatario) {
        $usuarios = [];

        if ($destinatario === 'todos') {
            $usuarios = obtenerUsuariosSinFiltro($conn); // Todos los usuarios
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

// Obtener lista de usuarios para el formulario
$usuarios = obtenerUsuariosSinFiltro($conn);

// Obtener las notificaciones ya enviadas
$notificaciones = [];
$result = $conn->query("
    SELECT n.id_notificacion, u.nombre, n.mensaje, n.fecha, n.leida 
    FROM notificacion n
    INNER JOIN usuario u ON n.id_usuario = u.id_usuario
    ORDER BY n.fecha DESC
");

if ($result) {
    $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
}

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
                <textarea name="mensaje" id="mensaje" required class="input-general" placeholder="Tu mensaje" required></textarea>
                <button type="submit" class="btn-general">Enviar Notificación</button>
            </div>
        </form>
    </section>

    <a href="notificaciones_enviadas.php" class="btn-general">Ver Notificaciones Enviadas</a>
    <!-- Mostrar notificaciones dirigidas al administrador -->
    <section>
        <h3>Mis Notificaciones</h3>
        <?php
        // Configuración de paginación
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10; // Número de notificaciones por página
        $offset = ($page - 1) * $limit;

        // Obtener el número total de notificaciones del administrador
        $total_query = $conn->prepare("SELECT COUNT(*) AS total FROM notificacion WHERE id_usuario = ?");
        $total_query->bind_param("i", $_SESSION['id_usuario']);
        $total_query->execute();
        $total_result = $total_query->get_result()->fetch_assoc();
        $total_notificaciones = $total_result['total'];
        $total_pages = ceil($total_notificaciones / $limit);

        // Obtener las notificaciones del administrador con límite y offset
        $query = $conn->prepare("
            SELECT mensaje, fecha, leida 
            FROM notificacion 
            WHERE id_usuario = ? 
            ORDER BY fecha DESC 
            LIMIT ? OFFSET ?
        ");
        $query->bind_param("iii", $_SESSION['id_usuario'], $limit, $offset);
        $query->execute();
        $notificaciones = $query->get_result()->fetch_all(MYSQLI_ASSOC);
        marcarNotificacionesComoLeidas($conn, $_SESSION['id_usuario']);
        // Mostrar las notificaciones
        if (empty($notificaciones)): ?>
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

            <!-- Paginación -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="notificaciones.php?page=<?php echo $page - 1; ?>" class="btn-general">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="notificaciones.php?page=<?php echo $i; ?>" class="btn-general <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="notificaciones.php?page=<?php echo $page + 1; ?>" class="btn-general">Siguiente</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
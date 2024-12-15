<?php
require_once('../includes/general.php');
require_once('../miembros/member_functions.php');

// Verificar que el usuario sea administrador
verificarAdmin();

$conn = obtenerConexion();
$title = "Gestión de Notificaciones";

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
    <h2>Gestión de Notificaciones</h2>

    <!-- Mostrar mensajes de éxito o error -->
    <?php if (isset($success)): ?>
        <div class="mensaje-exito"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Formulario para enviar notificaciones -->
    <section>
        <h3>Enviar Notificación</h3>

        <!-- Mostrar mensajes de éxito o error -->
        <?php if (isset($success)): ?>
            <div class="mensaje-confirmacion"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>


        <form method="POST" action="notificaciones.php" class="form_container">
            <label for="destinatario">Seleccionar Destinatario:</label>
            <select name="destinatario" id="destinatario" required onchange="toggleDestinatario()">
                <option value="">-- Selecciona un destinatario --</option>
                <option value="todos">Todos los Usuarios</option>
                <option value="grupo">Grupo Específico</option>
                <option value="usuario">Usuario Específico</option>
            </select>

            <div id="grupo_destinatario" style="display: none;">
                <label for="grupo">Seleccionar Grupo:</label>
                <select name="grupo" id="grupo">
                    <option value="miembros">Miembros</option>
                    <option value="monitores">Monitores</option>
                    <option value="administradores">Administradores</option>
                </select>
            </div>

            <div id="usuario_destinatario" style="display: none;">
                <label for="id_usuario">Seleccionar Usuario:</label>
                <select name="id_usuario" id="id_usuario">
                    <option value="">-- Selecciona un usuario --</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?php echo $usuario['id_usuario']; ?>">
                            <?php echo htmlspecialchars($usuario['nombre']); ?> (<?php echo htmlspecialchars($usuario['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label for="mensaje">Mensaje:</label>
            <textarea name="mensaje" id="mensaje" rows="5" required></textarea>

            <button type="submit" class="btn-general">Enviar Notificación</button>
        </form>
    </section>


    <!-- Lista de notificaciones enviadas -->
    <section>
        <h3>Notificaciones Enviadas</h3>
        <?php if (empty($notificaciones)): ?>
            <p>No se han enviado notificaciones.</p>
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
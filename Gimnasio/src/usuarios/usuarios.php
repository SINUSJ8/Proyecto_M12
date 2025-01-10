<?php

require_once('../usuarios/user_functions.php');

verificarAdmin();

$conn = obtenerConexion();
if (isset($_SESSION['mensaje'])) {
    // Determinar la clase según el contenido del mensaje
    $clase = 'success-message'; // Por defecto, mensaje de éxito
    if (strpos($_SESSION['mensaje'], 'no') !== false || strpos($_SESSION['mensaje'], 'existe') !== false) {
        $clase = 'mensaje-error';
    } elseif (strpos($_SESSION['mensaje'], 'restaurado') !== false) {
        $clase = 'mensaje-confirmacion';
    }
    // Mostrar el mensaje
    echo '<p class="' . $clase . '">' . htmlspecialchars($_SESSION['mensaje']) . '</p>';
    unset($_SESSION['mensaje']); // Elimina el mensaje después de mostrarlo
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurar_usuario'])) {
    $id_usuario = intval($_POST['id_usuario']);
    restaurarUsuario($conn, $id_usuario);
    $_SESSION['mensaje'] = "El usuario ha sido restaurado correctamente a un rol básico.";
    header('Location: usuarios.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    $id_usuario = intval($_POST['id_usuario']);

    // Verificar si el administrador intenta eliminar su propia cuenta
    if ($_SESSION['id_usuario'] === $id_usuario) {
        $_SESSION['mensaje'] = "No puedes eliminar tu propia cuenta.";
        header('Location: usuarios.php');
        exit();
    }

    // Verificar si el usuario existe
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close(); // Asegúrate de cerrar el statement

    if ($count === 0) {
        $_SESSION['mensaje'] = "El usuario no existe.";
        header('Location: usuarios.php');
        exit();
    }

    // Llamar a la función para eliminar el usuario
    eliminarUsuario($conn, $id_usuario);
    $_SESSION['mensaje'] = "El usuario ha sido eliminado correctamente.";
    header('Location: usuarios.php');
    exit();
}

// Capturar el término de búsqueda
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Obtener usuarios con la función personalizada
$sql = "SELECT id_usuario, nombre, email, rol, telefono, fecha_creacion FROM usuario WHERE 1=1";
if (!empty($busqueda)) {
    $sql .= " AND (nombre LIKE ? OR email LIKE ?)";
}
$sql .= " ORDER BY nombre ASC"; // Ordenamiento inicial por nombre
$stmt = $conn->prepare($sql);

if (!empty($busqueda)) {
    $busqueda_param = '%' . $busqueda . '%';
    $stmt->bind_param("ss", $busqueda_param, $busqueda_param);
}
$stmt->execute();
$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);

$title = "Gestión de Usuarios";
include '../admin/admin_header.php';

?>

<body>
    <main>
        <h2 class="section-title">Gestión de Usuarios</h2>

        <!-- Formulario de búsqueda -->
        <div class="form_container">
            <form method="GET" action="usuarios.php" class="search-form">
                <div class="input-container">
                    <input type="text" name="busqueda" placeholder="Buscar usuario..." value="<?php echo htmlspecialchars($busqueda); ?>" class="input-general">
                </div>
                <div class="buttons-container">
                    <button type="submit" class="btn-general">Buscar</button>
                    <a href="crear_usuario.php" class="btn-general" title="Crea una nueva cuenta de usuario">Crear Usuario</a>
                </div>
            </form>
        </div>


        <!-- Tabla con lista de usuarios -->
        <table id="tabla-usuarios" class="styled-table">
            <thead>
                <tr>
                    <th onclick="ordenarTablaU(0)" class="sortable">Nombre</th>
                    <th onclick="ordenarTablaU(1)" class="sortable">Email</th>
                    <th onclick="ordenarTablaU(2)" class="sortable">Rol</th>
                    <th onclick="ordenarTablaU(3)" class="sortable">Teléfono</th>
                    <th onclick="ordenarTablaU(4)" class="sortable">Fecha de Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($usuarios)): ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['telefono'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($usuario['fecha_creacion']); ?></td>
                            <td class="acciones">
                                <div class="button-container">
                                    <!-- Botón de editar -->
                                    <a href="<?php
                                                switch ($usuario['rol']) {
                                                    case 'miembro':
                                                        echo '../miembros/edit_miembro.php?id_usuario=' . $usuario['id_usuario'];
                                                        break;
                                                    case 'monitor':
                                                        echo '../monitores/edit_monitor.php?id_usuario=' . $usuario['id_usuario'];
                                                        break;
                                                    case 'admin':
                                                        echo '../admin/edit_admin.php?id_usuario=' . $usuario['id_usuario'];
                                                        break;
                                                    default:
                                                        echo '../usuarios/edit_usuario.php?id_usuario=' . $usuario['id_usuario'];
                                                        break;
                                                }
                                                ?>" class="btn-general edit-button">Editar</a>

                                    <!-- Botón para restaurar a "usuario" -->
                                    <form method="POST" action="usuarios.php" style="display:inline;" onsubmit="return confirmarRestauracion();">
                                        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                        <button type="submit" name="restaurar_usuario" class="btn-general delete-button">Restaurar</button>
                                    </form>
                                    <!-- Botón para eliminar usuario -->
                                    <form method="POST" action="usuarios.php" style="display:inline;" onsubmit="return confirmarEliminacion();">
                                        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                        <button type="submit" name="eliminar_usuario" class="delete-button">Eliminar</button>
                                    </form>

                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No se encontraron usuarios.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <?php
    include '../includes/footer.php';
    $conn->close();
    ?>
    <script src="../../assets/js/clases.js"></script>
    <script>
        function confirmarEliminacion() {
            return confirm("¿Estás seguro de que deseas eliminar este usuario?");
        }
    </script>


</body>
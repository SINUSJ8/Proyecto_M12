<?php

require_once('../usuarios/user_functions.php');

verificarAdmin();

$conn = obtenerConexion();

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['mensaje'])) {
    $clase = obtenerClaseMensaje($_SESSION['mensaje']);
    echo '<p class="' . $clase . '">' . htmlspecialchars($_SESSION['mensaje']) . '</p>';
    unset($_SESSION['mensaje']);
}

// Procesar solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = intval($_POST['id_usuario']);

    if ($_SESSION['id_usuario'] !== $id_usuario && $_SESSION['id_usuario'] !== 1) {
        $_SESSION['mensaje'] = "No tienes permisos para realizar esta acción.";
        header('Location: usuarios.php');
        exit();
    }

    if (isset($_POST['restaurar_usuario'])) {
        if ($id_usuario === 1 && $_SESSION['id_usuario'] !== 1) {
            $_SESSION['mensaje'] = "No puedes restaurar al administrador general.";
            header('Location: usuarios.php');
            exit();
        }
        restaurarUsuario($conn, $id_usuario);
        $_SESSION['mensaje'] = "El usuario ha sido restaurado correctamente a un rol básico.";
    } elseif (isset($_POST['eliminar_usuario'])) {
        if ($id_usuario === 1 || ($_SESSION['id_usuario'] !== 1 && $usuario['rol'] === 'admin' && $_SESSION['id_usuario'] !== 1)) {
            $_SESSION['mensaje'] = "No tienes permisos para eliminar este usuario.";
            header('Location: usuarios.php');
            exit();
        }
        eliminarUsuario($conn, $id_usuario);
        $_SESSION['mensaje'] = "El usuario ha sido eliminado correctamente.";
    }
    header('Location: usuarios.php');
    exit();
}


// Obtener usuarios
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$filtro_rol = isset($_GET['filtro_rol']) ? $_GET['filtro_rol'] : '';

$sql = "SELECT id_usuario, nombre, email, rol, telefono, fecha_creacion FROM usuario WHERE 1=1";

if (!empty($busqueda)) {
    $sql .= " AND (nombre LIKE ? OR email LIKE ?)";
}

if (!empty($filtro_rol)) {
    $sql .= " AND rol = ?";
}

$sql .= " ORDER BY nombre ASC";

$stmt = $conn->prepare($sql);

if (!empty($busqueda) && !empty($filtro_rol)) {
    $busqueda_param = '%' . $busqueda . '%';
    $stmt->bind_param("sss", $busqueda_param, $busqueda_param, $filtro_rol);
} elseif (!empty($busqueda)) {
    $busqueda_param = '%' . $busqueda . '%';
    $stmt->bind_param("ss", $busqueda_param, $busqueda_param);
} elseif (!empty($filtro_rol)) {
    $stmt->bind_param("s", $filtro_rol);
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
                <div class="input-container">
                    <select name="filtro_rol" class="input-general">
                        <option value="">Todos los roles</option>
                        <option value="admin" <?php echo (isset($_GET['filtro_rol']) && $_GET['filtro_rol'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                        <option value="monitor" <?php echo (isset($_GET['filtro_rol']) && $_GET['filtro_rol'] === 'monitor') ? 'selected' : ''; ?>>Monitor</option>
                        <option value="miembro" <?php echo (isset($_GET['filtro_rol']) && $_GET['filtro_rol'] === 'miembro') ? 'selected' : ''; ?>>Miembro</option>
                        <option value="usuario" <?php echo (isset($_GET['filtro_rol']) && $_GET['filtro_rol'] === 'usuario') ? 'selected' : ''; ?>>Usuario</option>
                    </select>
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
                        <?php
                        $nombre = htmlspecialchars($usuario['nombre']);
                        $email = htmlspecialchars($usuario['email']);
                        $rol = htmlspecialchars($usuario['rol']);
                        $telefono = htmlspecialchars($usuario['telefono'] ?? 'N/A');
                        $fecha_creacion = htmlspecialchars($usuario['fecha_creacion']);
                        ?>
                        <tr>
                            <td><?php echo $nombre; ?></td>
                            <td><?php echo $email; ?></td>
                            <td><?php echo $rol; ?></td>
                            <td><?php echo $telefono; ?></td>
                            <td><?php echo $fecha_creacion; ?></td>
                            <td class="acciones">
                                <div class="button-container">
                                    <!-- Botón de editar -->
                                    <?php if ($_SESSION['id_usuario'] === 1 || $_SESSION['id_usuario'] === $usuario['id_usuario'] || $usuario['rol'] !== 'admin'): ?>
                                        <a href="../usuarios/edit_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn-general edit-button">
                                            <?php echo ($_SESSION['id_usuario'] === $usuario['id_usuario']) ? 'Perfil' : 'Editar'; ?>
                                        </a>
                                    <?php endif; ?>


                                    <!-- Botón para restaurar a "usuario" -->
                                    <?php if ($_SESSION['id_usuario'] === 1 && $usuario['id_usuario'] !== 1): ?>
                                        <form method="POST" action="usuarios.php" style="display:inline;" onsubmit="return confirmarRestauracion();">
                                            <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                            <button type="submit" name="restaurar_usuario" class="btn-general delete-button">Restaurar</button>
                                        </form>
                                    <?php endif; ?>


                                    <!-- Botón para eliminar usuario -->
                                    <?php if ($_SESSION['id_usuario'] === 1 && $usuario['id_usuario'] !== 1): ?>
                                        <form method="POST" action="usuarios.php" style="display:inline;" onsubmit="return confirmarEliminacion();">
                                            <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                                            <button type="submit" name="eliminar_usuario" class="delete-button">Eliminar</button>
                                        </form>
                                    <?php endif; ?>

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

        function confirmarRestauracion() {
            return confirm("¿Estás seguro de que deseas restaurar este usuario a un rol básico?");
        }
    </script>
</body>
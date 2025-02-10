<?php

require_once('../usuarios/user_functions.php');

verificarAdmin();

$conn = obtenerConexion();

//  Mostrar mensaje si existe en la URL
if (isset($_GET['mensaje'])) {
    $clase = (isset($_GET['type']) && $_GET['type'] === 'error') ? 'mensaje-error' : 'mensaje-confirmacion';
}

//  Procesar solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = intval($_POST['id_usuario']);

    //  Proteger al superadmin
    if ($id_usuario === 1) {
        header('Location: usuarios.php?mensaje=No puedes modificar o eliminar al superadministrador&type=error');
        exit();
    }

    //  Evitar que un usuario se elimine/modifique a sí mismo
    if ($id_usuario === $_SESSION['id_usuario']) {
        header('Location: usuarios.php?mensaje=No puedes modificar o eliminar tu propia cuenta&type=error');
        exit();
    }

    //  Verificar si el usuario existe
    $stmt = $conn->prepare("SELECT rol FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param('i', $id_usuario);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        header('Location: usuarios.php?mensaje=El usuario no existe&type=error');
        exit();
    }

    //  Proteger a otros administradores (excepto si el superadmin está eliminando)
    if ($usuario['rol'] === 'admin' && $_SESSION['id_usuario'] !== 1) {
        header('Location: usuarios.php?mensaje=No tienes permiso para modificar o eliminar a otro administrador&type=error');
        exit();
    }

    //  Acción de eliminación de usuario
    if (isset($_POST['eliminar_usuario'])) {
        if (eliminarUsuario($conn, $id_usuario)) {
            header('Location: usuarios.php?mensaje=El usuario ha sido eliminado correctamente&type=confirmacion');
        } else {
            header('Location: usuarios.php?mensaje=Error al eliminar el usuario&type=error');
        }
        exit();
    }

    //  Si llega aquí sin acción, redirige con un mensaje de error
    header('Location: usuarios.php?mensaje=Acción no válida&type=error');
    exit();
}



// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8; // Número de resultados por página
$offset = ($page - 1) * $limit;

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

$sql .= " ORDER BY nombre ASC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

if (!empty($busqueda) && !empty($filtro_rol)) {
    $busqueda_param = '%' . $busqueda . '%';
    $stmt->bind_param("sssii", $busqueda_param, $busqueda_param, $filtro_rol, $limit, $offset);
} elseif (!empty($busqueda)) {
    $busqueda_param = '%' . $busqueda . '%';
    $stmt->bind_param("ssii", $busqueda_param, $busqueda_param, $limit, $offset);
} elseif (!empty($filtro_rol)) {
    $stmt->bind_param("sii", $filtro_rol, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);

// Obtener el número total de resultados
// Construir la consulta SQL base
$sql = "SELECT id_usuario, nombre, email, rol, telefono, fecha_creacion FROM usuario WHERE 1=1";
$params = [];
$types = "";

// Manejar búsqueda por nombre o email
if (!empty($busqueda)) {
    $sql .= " AND (nombre LIKE ? OR email LIKE ?)";
    $busqueda_param = '%' . $busqueda . '%';
    $params[] = $busqueda_param; // Primer parámetro
    $params[] = $busqueda_param; // Segundo parámetro
    $types .= "ss"; // Dos strings
}

// Manejar filtro por rol
if (!empty($filtro_rol)) {
    $sql .= " AND rol = ?";
    $params[] = $filtro_rol; // Agregar el filtro de rol
    $types .= "s"; // Un string
}

// Agregar límites y ordenación para la paginación
$sql .= " ORDER BY nombre ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii"; // Dos enteros

// Preparar la consulta
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

// Enlazar los parámetros
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

// Ejecutar la consulta
$stmt->execute();
$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);

// Obtener el número total de resultados
$sql_count = "SELECT COUNT(*) as total FROM usuario WHERE 1=1";
$params_count = [];
$types_count = "";

// Reutilizar condiciones para la consulta de conteo
if (!empty($busqueda)) {
    $sql_count .= " AND (nombre LIKE ? OR email LIKE ?)";
    $params_count[] = $busqueda_param;
    $params_count[] = $busqueda_param;
    $types_count .= "ss";
}

if (!empty($filtro_rol)) {
    $sql_count .= " AND rol = ?";
    $params_count[] = $filtro_rol;
    $types_count .= "s";
}

$stmt_count = $conn->prepare($sql_count);

if (!$stmt_count) {
    die("Error en la preparación de la consulta de conteo: " . $conn->error);
}

if (!empty($params_count)) {
    $stmt_count->bind_param($types_count, ...$params_count);
}

$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_rows = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);


$total_pages = ceil($total_rows / $limit);

$title = "Gestión de Usuarios";
include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2 class="section-title">Gestión de Usuarios</h2>
        <?php if (isset($_GET['mensaje'])): ?>
            <?php $clase = (isset($_GET['type']) && $_GET['type'] === 'error') ? 'mensaje-error' : 'mensaje-confirmacion'; ?>
            <div id="mensaje-flotante" class="<?= $clase; ?>">
                <?= htmlspecialchars($_GET['mensaje']); ?>
            </div>
        <?php endif; ?>
        <!-- Formulario de búsqueda -->
        <div class="form_container">
            <form method="GET" action="usuarios.php" class="search-form">
                <div class="form-inline">
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
                    <div class="button-container">
                        <button type="submit" class="btn-general">Buscar</button>
                        <a href="usuarios.php" class="btn-general limpiar-busqueda">Limpiar</a>
                        <a href="crear_usuario.php" class="btn-general" title="Crea una nueva cuenta de usuario">Crear Usuario</a>
                    </div>
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
                            <td><?php echo date("d/m/Y", strtotime($usuario['fecha_creacion'])); ?></td>
                            <td>
                                <div class="button-container">
                                    <?php
                                    // Caso 1: Superadministrador (id_usuario = 1)
                                    if ($_SESSION['id_usuario'] === 1):
                                        // Botón de editar propio como "Perfil"
                                        if ($_SESSION['id_usuario'] === $usuario['id_usuario']): ?>
                                            <a href="edit_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn-general">Perfil</a>
                                        <?php else: ?>
                                            <!-- Botones activos para superadmin -->
                                            <a href="edit_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn-general">Editar</a>
                                            <button type="button" class="delete-button" onclick="confirmarEliminacionUsuario(<?php echo $usuario['id_usuario']; ?>)">Eliminar</button>

                                        <?php endif; ?>
                                        <?php
                                    // Caso 2: Otros administradores
                                    elseif ($usuario['rol'] === 'admin'):
                                        // Deshabilitar botones para otros administradores
                                        if ($_SESSION['id_usuario'] === $usuario['id_usuario']): ?>
                                            <a href="edit_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn-general">Perfil</a>
                                        <?php else: ?>
                                            <button class="btn-general btn-disabled" title="No tienes permisos para editar este administrador" disabled>Editar</button>
                                            <button class="delete-button btn-disabled" title="No tienes permisos para eliminar este administrador" disabled>Eliminar</button>
                                        <?php endif; ?>
                                    <?php
                                    // Caso 3: Usuarios no administradores
                                    else: ?>
                                        <a href="edit_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn-general">Editar</a>
                                        <button type="button" class="delete-button" onclick="confirmarEliminacionUsuario(<?php echo $usuario['id_usuario']; ?>)">Eliminar</button>

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
        <!-- Formulario oculto para eliminar usuarios -->
        <form id="form-eliminar" method="POST" action="usuarios.php" style="display: none;">
            <input type="hidden" name="id_usuario" id="id_usuario">
            <input type="hidden" name="eliminar_usuario" value="1">
        </form>


        <!-- Paginación -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="usuarios.php?page=<?php echo $page - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&filtro_rol=<?php echo urlencode($filtro_rol); ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="usuarios.php?page=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>&filtro_rol=<?php echo urlencode($filtro_rol); ?>" class="btn-general <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="usuarios.php?page=<?php echo $page + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&filtro_rol=<?php echo urlencode($filtro_rol); ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../../assets/js/clases.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/alertas.js"></script>

</body>


</html>
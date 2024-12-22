<?php

require_once('../usuarios/user_functions.php');

verificarAdmin();

$conn = obtenerConexion();

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
                <div>
                    <input type="text" name="busqueda" placeholder="Buscar usuario..." value="<?php echo htmlspecialchars($busqueda); ?>" class="input-general">
                    <button type="submit" class="btn-general">Buscar</button>
                </div>
                <div style="margin-top: 10px;">
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
</body>
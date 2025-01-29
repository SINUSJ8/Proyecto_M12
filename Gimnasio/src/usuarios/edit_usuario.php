<?php
require_once('../usuarios/user_functions.php');

verificarAdmin();
$conn = obtenerConexion();

$title = "Editar Usuario";
include '../admin/admin_header.php';
if (!isset($_SESSION['referer']) && isset($_SERVER['HTTP_REFERER'])) {
    $_SESSION['referer'] = $_SERVER['HTTP_REFERER'];
}

// Obtener datos del usuario si se accede mediante GET o después de la actualización
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id_usuario'])) {
    $id_usuario = $_GET['id_usuario'];
    $datos_usuario = obtenerDatosUsuario($conn, $id_usuario);

    if (!$datos_usuario) {
        echo "Usuario no encontrado";
        exit;
    }
}

// Procesar la actualización si se envía el formulario (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'];
    $nuevo_nombre = $_POST['nombre'];
    $nuevo_email = $_POST['email'];
    $nuevo_telefono = $_POST['telefono'];
    $nuevo_rol = $_POST['rol'];
    $nueva_contrasenya = $_POST['contrasenya'] ?: null;

    modUsuario($conn, $id_usuario, $nuevo_nombre, $nuevo_email, $nuevo_telefono, $nuevo_rol, $nueva_contrasenya, "edit_usuario.php?id_usuario=" . urlencode($id_usuario));

    exit();
}

$conn->close();
?>

<body>
    <main>
        <h2 class="section-title">Editar Usuario</h2>

        <!-- Mostrar mensaje de confirmación si existe -->
        <?php if (isset($_GET['mensaje'])): ?>
            <?php $clase = (isset($_GET['type']) && $_GET['type'] === 'error') ? 'mensaje-error' : 'mensaje-confirmacion'; ?>
            <div id="mensaje-flotante" class="<?= $clase; ?>">
                <?= htmlspecialchars($_GET['mensaje']); ?>
            </div>
        <?php endif; ?>


        <div class="form_container">
            <form action="edit_usuario.php" method="POST" class="form_general" onsubmit="return validarFormulario();" data-context="edicion">
                <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($id_usuario); ?>">

                <!-- Campo para editar el nombre -->
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" class="input-general" value="<?php echo htmlspecialchars($datos_usuario['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required aria-label="Nombre completo del usuario">

                <!-- Campo para editar el email -->
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="input-general" value="<?php echo htmlspecialchars($datos_usuario['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required title="Introduce el email del usuario" aria-label="Correo electrónico del usuario">

                <!-- Selector para editar el rol -->
                <label for="rol">Rol:</label>
                <?php if ((int)$_SESSION['id_usuario'] === (int)$id_usuario): ?>
                    <small style="color:gray; font-size:0.9em;">(No puedes cambiar tu propio rol)</small>
                    <!-- Mostrar el rol como texto sin permitir edición -->
                    <input type="hidden" name="rol" value="<?php echo htmlspecialchars($datos_usuario['rol']); ?>">
                    <input type="text" id="rol" class="input-general" value="<?php echo htmlspecialchars($datos_usuario['rol']); ?>" readonly>
                <?php else: ?>
                    <!-- Selector de rol para administradores que no son el mismo -->
                    <select id="rol" name="rol" class="select-general" required>
                        <option value="usuario" <?php echo $datos_usuario['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                        <option value="miembro" <?php echo $datos_usuario['rol'] === 'miembro' ? 'selected' : ''; ?>>Miembro</option>
                        <option value="monitor" <?php echo $datos_usuario['rol'] === 'monitor' ? 'selected' : ''; ?>>Monitor</option>
                        <option value="admin" <?php echo $datos_usuario['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                <?php endif; ?>

                <!-- Campo para editar el teléfono -->
                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" class="input-general" value="<?php echo htmlspecialchars($datos_usuario['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="9" pattern="\d{9}" title="Debe contener exactamente 9 dígitos numéricos" autocomplete="off" aria-label="Número de teléfono del usuario">

                <!-- Campo para editar la contraseña -->
                <label for="contrasenya">Contraseña (dejar en blanco para no cambiarla):</label>
                <input type="password" id="contrasenya" name="contrasenya" class="input-general" autocomplete="new-password" aria-label="Nueva contraseña para el usuario" title="Introduce una nueva contraseña solo si deseas cambiarla">

                <!-- Campo para confirmar la contraseña -->
                <label for="confirmar_contrasenya">Confirmar Contraseña:</label>
                <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" class="input-general" autocomplete="new-password" aria-label="Confirmación de la nueva contraseña" title="Repite la nueva contraseña para confirmar">


                <div class="button-container">
                    <button type="submit" class="btn-general">Actualizar Datos</button>
                    <a href="<?= htmlspecialchars($_SESSION['referer']) ?>" class="btn-general btn-secondary" onclick="unsetReferer()">Cancelar</a>

                </div>
            </form>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/validacion.js"></script>
</body>
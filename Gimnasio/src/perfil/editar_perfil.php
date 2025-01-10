<?php
session_start();
require_once('perfil_functions.php');
require_once('../includes/general.php');

// Verificar que el usuario está autenticado
verificarSesion();
$conn = obtenerConexion();
$title = "Editar Perfil";

// Obtener los datos del usuario autenticado
$id_usuario = $_SESSION['id_usuario'];
$usuario = obtenerDetalleUsuario($conn, $id_usuario);

if (!$usuario) {
    die("Usuario no encontrado.");
}

// Manejo del formulario
$mensaje = '';
$mensaje_tipo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['contrasenya'] ?? '';
    $confirmarPassword = $_POST['confirmar_contrasenya'] ?? '';

    // Validaciones en el servidor
    $errores = [];
    if (empty($nombre) || !preg_match('/[a-zA-Z]/', $nombre)) {
        $errores[] = "El nombre es obligatorio y debe contener al menos una letra.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Por favor, introduce un correo electrónico válido.";
    }
    if (!empty($telefono) && !preg_match('/^\d{9}$/', $telefono)) {
        $errores[] = "El teléfono debe tener exactamente 9 dígitos.";
    }
    if (!empty($password) && strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    if (!empty($password) && $password !== $confirmarPassword) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    if (empty($errores)) {
        $actualizado = actualizarUsuario($conn, $id_usuario, $nombre, $email, $telefono, $password);
        if ($actualizado) {
            $mensaje = "Perfil actualizado correctamente.";
            $mensaje_tipo = "success";

            // Recargar los datos del usuario después de la actualización
            $usuario = obtenerDetalleUsuario($conn, $id_usuario);
        } else {
            $mensaje = "Error al actualizar el perfil. Verifica los datos.";
            $mensaje_tipo = "error";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $mensaje_tipo = "error";
    }
}

include '../miembros/miembro_header.php';
?>

<body>
    <main>
        <h2 class="section-title">Editar Perfil</h2>

        <!-- Mostrar mensajes -->
        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo $mensaje_tipo === 'error' ? 'mensaje-error' : 'mensaje-confirmacion'; ?>">
                <p><?php echo $mensaje; ?></p>
            </div>
        <?php endif; ?>

        <!-- Formulario de edición -->
        <div class="form_container">
            <form method="POST" action="editar_perfil.php" class="form_general" onsubmit="return valFormUsuario();">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" class="input-general"
                    value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>

                <label for="email">Correo electrónico:</label>
                <input type="email" id="email" name="email" class="input-general"
                    value="<?php echo htmlspecialchars($usuario['email']); ?>" required>

                <label for="telefono">Teléfono (opcional):</label>
                <input type="text" id="telefono" name="telefono" class="input-general"
                    value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">

                <label for="contrasenya">Nueva Contraseña:</label>
                <input type="password" id="contrasenya" name="contrasenya" class="input-general">

                <label for="confirmar_contrasenya">Confirmar Contraseña:</label>
                <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" class="input-general">

                <button type="submit" class="btn-general">Guardar Cambios</button>
                <a href="../miembros/miembro.php" class="btn-general cancel-button">Cancelar</a>
            </form>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../../assets/js/validacion.js"></script>
</body>
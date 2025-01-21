<?php
require_once('../usuarios/user_functions.php');
require_once('../includes/general.php');

verificarAdmin();

$conn = obtenerConexion();

$title = "Crear Usuario";
include '../admin/admin_header.php';

// Inicializar mensajes
$mensaje = null;
$tipo_mensaje = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_usuario'])) {
    // Capturar los datos del formulario
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contrasenya = $_POST['contrasenya'];
    $confirmar_contrasenya = $_POST['confirmar_contrasenya'];
    $rol = $_POST['rol'];

    // Validaciones en el servidor
    if (!preg_match('/[a-zA-Z]/', $nombre)) {
        $mensaje = "Por favor, ingresa un nombre válido con al menos una letra.";
        $tipo_mensaje = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Por favor, ingresa un correo electrónico válido.";
        $tipo_mensaje = "error";
    } elseif ($contrasenya !== $confirmar_contrasenya) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = "error";
    } elseif (strlen($contrasenya) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
        $tipo_mensaje = "error";
    } else {
        // Llamar a la función para crear el usuario
        try {
            crearFormUsuario($conn, $nombre, $email, $contrasenya, $confirmar_contrasenya, 'usuario');
            $mensaje = "Usuario creado exitosamente.";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error al crear el usuario: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

?>

<body>
    <main>
        <!-- Mostrar mensaje de error o éxito -->
        <?php if ($mensaje): ?>
            <div id="mensaje-flotante" class="<?php echo $tipo_mensaje === 'error' ? 'mensaje-error' : 'mensaje-confirmacion'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear un nuevo usuario -->
        <section class="form_container">
            <h3 class="section-title">Agregar Usuario Manualmente</h3>
            <form action="crear_usuario.php" method="POST" class="form_general" onsubmit="return validarFormulario()">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" class="input-general" required value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>">

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="input-general" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

                <label for="contrasenya">Contraseña:</label>
                <input type="password" id="contrasenya" name="contrasenya" class="input-general" required>

                <label for="confirmar_contrasenya">Confirmar Contraseña:</label>
                <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" class="input-general" required>

                <input type="hidden" name="rol" value="usuario">

                <button type="submit" name="crear_usuario" class="btn-general">Crear Usuario</button>
                <a href="usuarios.php" class="btn-general btn-secondary">Volver a Usuarios</a>
            </form>
        </section>
    </main>

    <script src="../../assets/js/validacion.js"></script>
    <script>
        manejarMensajeServidor();
    </script>
    <?php include '../includes/footer.php'; ?>
</body>

</html>
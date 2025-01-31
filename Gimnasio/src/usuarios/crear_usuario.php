<?php
require_once('../usuarios/user_functions.php');
require_once('../includes/general.php');

verificarAdmin();

$conn = obtenerConexion();

$title = "Crear Usuario";
include '../admin/admin_header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_usuario'])) {
    // Capturar los datos del formulario
    $_SESSION['form_data'] = $_POST; // Guardar los datos en la sesión

    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contrasenya = $_POST['contrasenya'];
    $confirmar_contrasenya = $_POST['confirmar_contrasenya'];
    $rol = $_POST['rol'];

    // Validaciones en el servidor
    if (!preg_match('/[a-zA-Z]/', $nombre)) {
        $_SESSION['error'] = "Por favor, ingresa un nombre válido con al menos una letra.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Por favor, ingresa un correo electrónico válido.";
    } elseif ($contrasenya !== $confirmar_contrasenya) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
    } elseif (strlen($contrasenya) < 6) {
        $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Llamar a la función para crear el usuario
        try {
            crearFormUsuario($conn, $nombre, $email, $contrasenya, $confirmar_contrasenya, 'usuario');
            $_SESSION['mensaje'] = "Usuario creado exitosamente.";
            unset($_SESSION['form_data']); // Limpiar los datos del formulario si fue exitoso
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al crear el usuario: " . $e->getMessage();
        }
    }

    // Redirigir para evitar que el formulario se reenvíe al recargar la página
    header("Location: crear_usuario.php");
    exit();
}



?>

<body>
    <main>
        <!-- Mostrar mensaje de confirmación -->
        <?php if (isset($_SESSION['mensaje_confirmacion'])): ?>
            <div id="mensaje-flotante" class="mensaje-confirmacion">
                <p><?php echo htmlspecialchars($_SESSION['mensaje_confirmacion']); ?></p>
            </div>
            <?php unset($_SESSION['mensaje_confirmacion'], $_SESSION['form_data']); // Borrar todos los datos al éxito 
            ?>
        <?php endif; ?>

        <!-- Mostrar mensaje de error -->
        <?php if (isset($_SESSION['mensaje_error'])): ?>
            <div id="mensaje-flotante" class="mensaje-error">
                <p><?php echo htmlspecialchars($_SESSION['mensaje_error']); ?></p>
            </div>
            <?php unset($_SESSION['mensaje_error']); ?>
        <?php endif; ?>

        <!-- Formulario para crear un nuevo usuario -->
        <section class="form_container">
            <h3 class="section-title">Agregar Usuario Manualmente</h3>
            <form action="crear_usuario.php" method="POST" class="form_general" onsubmit="return validarFormulario()">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" class="input-general" required
                    value="<?php echo isset($_SESSION['form_data']['nombre']) ? htmlspecialchars($_SESSION['form_data']['nombre']) : ''; ?>">

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="input-general" required
                    value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">

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
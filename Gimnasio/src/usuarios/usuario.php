<?php
session_start();
require_once('../usuarios/user_functions.php');
include '../usuarios/user_header.php';

$conn = obtenerConexion();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php?error=Debes+iniciar+sesión+primero");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener datos actuales del usuario
$datos_usuario = obtenerDatosUsuario($conn, $id_usuario);

// Procesar la actualización de datos cuando se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nuevo_nombre = trim($_POST['nombre']);
    $nuevo_telefono = trim($_POST['telefono']);
    $nueva_contrasenya = trim($_POST['contrasenya']);
    $confirmar_contrasenya = trim($_POST['confirmar_contrasenya']);

    // Validaciones del lado del servidor
    if (!preg_match('/[a-zA-Z]/', $nuevo_nombre)) {
        $_SESSION['error'] = "El nombre debe contener al menos una letra.";
        header("Location: usuario.php");
        exit();
    }

    if (!empty($nuevo_telefono) && !preg_match('/^\d{9}$/', $nuevo_telefono)) {
        $_SESSION['error'] = "El teléfono debe contener exactamente 9 dígitos numéricos.";
        header("Location: usuario.php");
        exit();
    }

    if (!empty($nueva_contrasenya)) {
        if (strlen($nueva_contrasenya) < 6) {
            $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres.";
            header("Location: usuario.php");
            exit();
        }

        if ($nueva_contrasenya !== $confirmar_contrasenya) {
            $_SESSION['error'] = "Las contraseñas no coinciden.";
            header("Location: usuario.php");
            exit();
        }
    }

    // Actualizar los datos del usuario
    actualizarDatosUsuario($conn, $id_usuario, $nuevo_nombre, $nuevo_telefono, $nueva_contrasenya ?: null, "usuario.php?mensaje=Datos+actualizados+correctamente");
}

$conn->close();
?>

<main class="form_container">
    <h1 class="section-title">Perfil del Usuario</h1>

    <!-- Mensajes del servidor -->
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="mensaje-confirmacion">
            <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mensaje-error">
            <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- SECCIÓN: Información Actual -->
    <section class="info-box">
        <h2>Información del Usuario</h2>
        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($datos_usuario['nombre']); ?></p>
        <p><strong>Correo Electrónico:</strong> <?php echo htmlspecialchars($datos_usuario['email']); ?></p>
        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($datos_usuario['telefono'] ?: 'No disponible'); ?></p>
    </section>

    <!-- SECCIÓN: Edición de Datos -->
    <section class="perfil-edicion">
        <h2>Actualizar Información</h2>
        <form action="usuario.php" method="POST" onsubmit="return valFormUsuario();">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($datos_usuario['nombre']); ?>" required
                title="Ingresa tu nombre completo. Debe contener al menos una letra.">

            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($datos_usuario['email']); ?>" disabled
                title="Tu correo electrónico no se puede modificar.">

            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($datos_usuario['telefono']); ?>" maxlength="9" pattern="\d{9}"
                title="Debe contener exactamente 9 dígitos numéricos." autocomplete="off">

            <h3>Cambiar Contraseña</h3>
            <p class="info-text">Si no deseas cambiar tu contraseña, deja estos campos en blanco.</p>

            <label for="contrasenya">Nueva Contraseña:</label>
            <input type="password" id="contrasenya" name="contrasenya" autocomplete="new-password"
                title="Debe contener al menos 6 caracteres.">

            <label for="confirmar_contrasenya">Confirmar Nueva Contraseña:</label>
            <input type="password" id="confirmar_contrasenya" name="confirmar_contrasenya" autocomplete="new-password"
                title="Debe coincidir con la nueva contraseña ingresada.">

            <div class="button-container">
                <button type="submit" class="btn-general">Guardar Cambios</button>
            </div>
        </form>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
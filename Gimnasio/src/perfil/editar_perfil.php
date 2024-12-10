<?php
session_start(); // Asegúrate de iniciar la sesión al comienzo

$title = "Editar Perfil";
include '../miembros/miembro_header.php';
require_once '../miembros/member_functions.php';
require_once 'perfil_functions.php';

// Conexión a la base de datos
$conn = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $id_membresia = isset($_POST['id_membresia']) ? $_POST['id_membresia'] : null;

    // Validación de datos
    $errores = [];
    if (empty($nombre) || !preg_match('/[a-zA-Z]/', $nombre)) {
        $errores[] = "Por favor, ingresa un nombre válido.";
    }
    if (!empty($telefono) && !preg_match('/^\d{9}$/', $telefono)) {
        $errores[] = "El teléfono debe tener exactamente 9 dígitos.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Por favor, ingresa un correo electrónico válido.";
    }

    if (!empty($errores)) {
        // Almacena los errores en la sesión
        $_SESSION['error'] = implode('<br>', $errores);
        header('Location: editar_perfil.php');
        exit;
    }

    // Llama a la función para actualizar los datos
    $resultado = actualizarPerfil($conn, $id_usuario, $nombre, $email, $telefono, $id_membresia);

    if ($resultado['success']) {
        $_SESSION['mensaje'] = "Perfil actualizado correctamente.";
        header('Location: editar_perfil.php');
        exit;
    } else {
        $_SESSION['error'] = "Error: " . $resultado['message'];
        header('Location: editar_perfil.php');
        exit;
    }
}

// Obtener la información del miembro para mostrar en el formulario
$miembro = obtenerMiembroPorIDPerfil($conn, $id_usuario);

if (!$miembro) {
    echo "No se encontró información para este miembro.";
    exit;
}

// Obtener las membresías disponibles
$membresias = obtenerMembresias($conn);
?>

<!-- Contenedor principal -->
<main class="form_container">
    <h1>Editar Perfil</h1>

    <!-- Mostrar mensajes de error o confirmación -->
    <?php
    if (isset($_SESSION['error'])) {
        echo "<p class='mensaje-error'>{$_SESSION['error']}</p>";
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['mensaje'])) {
        echo "<p class='mensaje-confirmacion'>{$_SESSION['mensaje']}</p>";
        unset($_SESSION['mensaje']);
    }
    ?>

    <!-- Formulario de edición -->
    <form action="editar_perfil.php" method="POST" onsubmit="return valFormUsuario();">
        <!-- Nombre -->
        <label for="nombre">Nombre Completo:</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($miembro['nombre']); ?>" required>

        <!-- Email -->
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($miembro['email']); ?>" required>

        <!-- Teléfono -->
        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($miembro['telefono']); ?>" pattern="\d{9}" title="Debe tener exactamente 9 dígitos." required>

        <!-- Membresía Actual -->
        <label for="id_membresia">Membresía:</label>
        <select id="id_membresia" name="id_membresia">
            <option value="">Seleccionar...</option>
            <?php foreach ($membresias as $membresia): ?>
                <option value="<?php echo $membresia['id_membresia']; ?>"
                    <?php echo $membresia['id_membresia'] == $miembro['id_membresia'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($membresia['tipo']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Botón de envío -->
        <button type="submit" class="btn-general">Guardar Cambios</button>
    </form>
</main>

<!-- Footer -->
<?php include '../includes/footer.php'; ?>

<!-- Enlace al archivo de validación -->
<script src="../assets/js/validacion.js"></script>
<?php
session_start();
require_once('../includes/general.php');
require_once('perfil_functions.php');

verificarSesion();
$conn = obtenerConexion();
$title = "Perfil del Usuario";

$id_usuario = $_SESSION['id_usuario'];
$usuario = obtenerDetalleUsuario($conn, $id_usuario);

if (!$usuario) {
    die("Usuario no encontrado.");
}

// Manejo del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono'] ?? '');
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

    if (!preg_match('/[a-zA-Z]/', $nombre)) {
        $error = "Por favor, ingresa un nombre válido con al menos una letra.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, introduce un correo electrónico válido.";
    } elseif (!empty($telefono) && !preg_match('/^\d{9}$/', $telefono)) {
        $error = "El teléfono debe tener exactamente 9 dígitos.";
    }

    // Validación de cambio de contraseña
    if (!empty($contrasena_actual) || !empty($nueva_contrasena) || !empty($confirmar_contrasena)) {
        if (empty($contrasena_actual) || empty($nueva_contrasena) || empty($confirmar_contrasena)) {
            $error = "Para cambiar la contraseña, debes completar todos los campos.";
        } elseif (empty($usuario['contrasenya']) || !password_verify($contrasena_actual, $usuario['contrasenya'])) {
            $error = "La contraseña actual es incorrecta o no está establecida.";
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $error = "Las nuevas contraseñas no coinciden.";
        } elseif (strlen($nueva_contrasena) < 6) {
            $error = "La nueva contraseña debe tener al menos 6 caracteres.";
        }
    }

    if (!isset($error)) {
        $sql = "UPDATE usuario SET nombre = ?, email = ?, telefono = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nombre, $email, $telefono, $id_usuario);
        $stmt->execute();

        // Si se va a cambiar la contraseña, actualizarla
        if (!empty($nueva_contrasena)) {
            $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $sql = "UPDATE usuario SET contrasenya = ? WHERE id_usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nueva_contrasena_hash, $id_usuario);
            $stmt->execute();
        }

        header("Location: editar_perfil.php?success=Perfil+actualizado+correctamente");
        exit();
    }
}

include '../miembros/miembro_header.php';
?>

<main class="form_container">
    <h1 class="section-title" title="Información y configuración de tu perfil.">Perfil del Usuario</h1>

    <?php if (isset($_GET['success'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: "¡Éxito!",
                    text: "<?php echo htmlspecialchars($_GET['success']); ?>",
                    icon: "success",
                    confirmButtonText: "Aceptar"
                });
            });
        </script>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: "¡Error!",
                    text: "<?php echo htmlspecialchars($error); ?>",
                    icon: "error",
                    confirmButtonText: "Aceptar"
                });
            });
        </script>
    <?php endif; ?>

    <section class="perfil-info">
        <h2 title="Datos personales asociados a tu cuenta.">Información del Usuario</h2>
        <div class="info-box">
            <p title="Tu nombre registrado en el sistema."><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre']); ?></p>
            <p title="Tu dirección de correo electrónico."><strong>Correo Electrónico:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
            <p title="Tu número de teléfono registrado."><strong>Teléfono:</strong> <?php echo htmlspecialchars($usuario['telefono'] ?? 'No disponible'); ?></p>
        </div>
    </section>

    <section class="perfil-edicion">
        <h2 title="Modificar los datos de tu cuenta.">Editar Información</h2>
        <form method="POST" action="editar_perfil.php">
            <label for="nombre" title="Escribe tu nombre completo.">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required title="Introduce tu nombre completo.">

            <label for="email" title="Introduce tu correo electrónico.">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required title="Introduce tu dirección de correo electrónico válida.">

            <label for="telefono" title="Introduce tu número de teléfono de 9 dígitos.">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" maxlength="9" pattern="\d{9}" title="Debe contener exactamente 9 dígitos numéricos.">

            <h2 title="Opciones para cambiar tu contraseña.">Cambiar Contraseña</h2>
            <label for="contrasena_actual" title="Introduce tu contraseña actual.">Contraseña Actual:</label>
            <input type="password" id="contrasena_actual" name="contrasena_actual" title="Introduce tu contraseña actual para poder cambiarla.">

            <label for="nueva_contrasena" title="Introduce una nueva contraseña segura.">Nueva Contraseña:</label>
            <input type="password" id="nueva_contrasena" name="nueva_contrasena" title="La nueva contraseña debe tener al menos 6 caracteres.">

            <label for="confirmar_contrasena" title="Confirma la nueva contraseña escribiéndola nuevamente.">Confirmar Nueva Contraseña:</label>
            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" title="Repite la nueva contraseña para confirmar que es correcta.">

            <button type="submit" class="btn-general" title="Guardar los cambios realizados en tu perfil.">Guardar Cambios</button>
        </form>
    </section>
</main>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
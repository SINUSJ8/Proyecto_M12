<?php
require_once('../includes/general.php');
require_once('../miembros/member_functions.php');
require_once('../includes/notificaciones_functions.php');
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'monitor') {
    header("Location: ../index.php?error=Acceso+denegado");
    exit();
}

$conn = obtenerConexion();
$title = "Perfil del Monitor";
$id_usuario = $_SESSION['id_usuario'];

$sql = "SELECT u.nombre, u.email, u.telefono, u.contrasenya, m.experiencia, m.disponibilidad 
    FROM usuario u 
    INNER JOIN monitor m ON u.id_usuario = m.id_usuario 
    WHERE u.id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$monitor = $result->fetch_assoc();

$sqlEspecialidades = "SELECT e.nombre AS especialidad 
                      FROM monitor_especialidad me 
                      INNER JOIN especialidad e ON me.id_especialidad = e.id_especialidad 
                      WHERE me.id_monitor = (SELECT id_monitor FROM monitor WHERE id_usuario = ?)";
$stmtEspecialidades = $conn->prepare($sqlEspecialidades);
$stmtEspecialidades->bind_param("i", $id_usuario);
$stmtEspecialidades->execute();
$resultEspecialidades = $stmtEspecialidades->get_result();
$especialidades = $resultEspecialidades->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $disponibilidad = $_POST['disponibilidad'] ?? $monitor['disponibilidad'];
    $contrasena_actual = $_POST['contrasena_actual'] ?? null;
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? null;
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? null;

    if (!preg_match('/[a-zA-Z]/', $nombre)) {
        $error = "Por favor, ingresa un nombre válido con al menos una letra.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingresa un correo electrónico válido.";
    } elseif ($telefono && !preg_match('/^\d{9}$/', $telefono)) {
        $error = "El teléfono debe tener exactamente 9 dígitos.";
    } elseif (!in_array($disponibilidad, ['disponible', 'no disponible'], true)) {
        $error = "Por favor, selecciona una disponibilidad válida.";
    }

    // Validación de cambio de contraseña
    if (!empty($contrasena_actual) || !empty($nueva_contrasena) || !empty($confirmar_contrasena)) {
        if (empty($contrasena_actual) || empty($nueva_contrasena) || empty($confirmar_contrasena)) {
            $error = "Para cambiar la contraseña, debes completar todos los campos.";
        } elseif (!password_verify($contrasena_actual, $monitor['contrasenya'])) {
            $error = "La contraseña actual es incorrecta.";
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $error = "Las nuevas contraseñas no coinciden.";
        } elseif (strlen($nueva_contrasena) < 6) {
            $error = "La nueva contraseña debe tener al menos 6 caracteres.";
        }
    }

    if (!isset($error)) {
        // Verificamos si la disponibilidad ha cambiado
        $cambio_de_disponibilidad = ($monitor['disponibilidad'] !== $disponibilidad);

        // Actualizar datos del usuario
        $sql = "UPDATE usuario SET nombre = ?, email = ?, telefono = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nombre, $email, $telefono, $id_usuario);
        $stmt->execute();

        // Actualizar disponibilidad del monitor
        $sql = "UPDATE monitor SET disponibilidad = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $disponibilidad, $id_usuario);
        $stmt->execute();

        // Si el monitor cambió su disponibilidad, notificar a los administradores
        if ($cambio_de_disponibilidad) {

            $sqlAdmins = "SELECT id_usuario FROM usuario WHERE rol = 'admin'";
            $resultAdmins = $conn->query($sqlAdmins);

            // Determinar el mensaje según el nuevo estado
            $estado_nuevo = ($disponibilidad === 'disponible') ? "Disponible" : "No Disponible";
            $mensaje = "El monitor " . htmlspecialchars($monitor['nombre']) . " ha cambiado su disponibilidad a '" . $estado_nuevo . "'.";

            while ($admin = $resultAdmins->fetch_assoc()) {
                enviarNotificacion($conn, $admin['id_usuario'], $mensaje);
            }
        }

        // Si se va a cambiar la contraseña, actualizarla
        if (!empty($nueva_contrasena)) {
            $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $sql = "UPDATE usuario SET contrasenya = ? WHERE id_usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nueva_contrasena_hash, $id_usuario);
            $stmt->execute();
        }

        header("Location: monitor.php?success=Perfil+actualizado+correctamente");
        exit();
    }
}

include 'monitores_header.php';
?>

<main class="form_container">
    <h1 class="section-title">Perfil del Monitor</h1>

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
        <h2>Información del Monitor</h2>
        <div class="info-box">
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($monitor['nombre']); ?></p>
            <p><strong>Correo Electrónico:</strong> <?php echo htmlspecialchars($monitor['email']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($monitor['telefono'] ?? 'No disponible'); ?></p>
            <p><strong>Experiencia:</strong> <?php echo htmlspecialchars($monitor['experiencia']); ?> años</p>
            <p><strong>Disponibilidad:</strong> <?php echo ucfirst(htmlspecialchars($monitor['disponibilidad'])); ?></p>
            <p><strong>Especialidades:</strong>
                <?php echo !empty($especialidades) ? implode(', ', array_column($especialidades, 'especialidad')) : 'No asignadas'; ?>
            </p>
        </div>
    </section>

    <section class="perfil-edicion">
        <h2>Editar Información</h2>
        <form method="POST" action="monitor.php">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($monitor['nombre']); ?>" required>

            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($monitor['email']); ?>" required>

            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($monitor['telefono']); ?>" maxlength="9" pattern="\d{9}" title="Debe contener exactamente 9 dígitos numéricos">

            <label for="disponibilidad">Disponibilidad:</label>
            <select id="disponibilidad" name="disponibilidad">
                <option value="disponible" <?php echo $monitor['disponibilidad'] === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                <option value="no disponible" <?php echo $monitor['disponibilidad'] === 'no disponible' ? 'selected' : ''; ?>>No Disponible</option>
            </select>

            <h2>Cambiar Contraseña</h2>
            <label for="contrasena_actual">Contraseña Actual:</label>
            <input type="password" id="contrasena_actual" name="contrasena_actual">

            <label for="nueva_contrasena">Nueva Contraseña:</label>
            <input type="password" id="nueva_contrasena" name="nueva_contrasena">

            <label for="confirmar_contrasena">Confirmar Nueva Contraseña:</label>
            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena">

            <button type="submit" class="btn-general">Guardar Cambios</button>
        </form>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
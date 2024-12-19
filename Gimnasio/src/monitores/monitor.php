<<?php
    require_once('../includes/general.php');
    require_once('../miembros/member_functions.php');

    // Verificar que el usuario es monitor
    if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'monitor') {
        header("Location: ../index.php?error=Acceso+denegado");
        exit();
    }

    $conn = obtenerConexion();
    $title = "Perfil del Monitor";
    $id_usuario = $_SESSION['id_usuario'];

    // Obtener información del monitor
    $sql = "SELECT u.nombre, u.email, u.telefono, m.experiencia, m.disponibilidad 
        FROM usuario u 
        INNER JOIN monitor m ON u.id_usuario = m.id_usuario 
        WHERE u.id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $monitor = $result->fetch_assoc();

    // Obtener especialidades del monitor
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
        $nombre = $_POST['nombre'] ?? $monitor['nombre'];
        $email = $_POST['email'] ?? $monitor['email'];
        $telefono = $_POST['telefono'] ?? $monitor['telefono'];
        $disponibilidad = $_POST['disponibilidad'] ?? $monitor['disponibilidad'];

        // Actualizar perfil del monitor
        $sql = "UPDATE usuario SET nombre = ?, email = ?, telefono = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nombre, $email, $telefono, $id_usuario);
        $stmt->execute();

        $sql = "UPDATE monitor SET disponibilidad = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $disponibilidad, $id_usuario);
        $stmt->execute();

        header("Location: monitor.php?success=Perfil+actualizado+correctamente");
        exit();
    }

    include 'monitores_header.php';
    ?>

    <main>
    <h2>Perfil del Monitor</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="mensaje-confirmacion"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <div class="form_container">
        <form method="POST" action="monitor.php" onsubmit="return validarFormularioEdicion('monitor');">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($monitor['nombre']); ?>" required>

            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($monitor['email']); ?>" required>

            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($monitor['telefono']); ?>" required>

            <label for="especialidad">Especialidades:</label>
            <ul>
                <?php foreach ($especialidades as $especialidad): ?>
                    <li><?php echo htmlspecialchars($especialidad['especialidad']); ?></li>
                <?php endforeach; ?>
            </ul>

            <label for="experiencia">Años de Experiencia:</label>
            <input type="number" id="experiencia" name="experiencia" value="<?php echo htmlspecialchars($monitor['experiencia']); ?>" readonly>

            <label for="disponibilidad">Disponibilidad:</label>
            <select id="disponibilidad" name="disponibilidad">
                <option value="disponible" <?php echo $monitor['disponibilidad'] === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                <option value="no disponible" <?php echo $monitor['disponibilidad'] === 'no disponible' ? 'selected' : ''; ?>>No Disponible</option>
            </select>

            <button type="submit" class="btn-general">Guardar Cambios</button>
        </form>

    </div>
    </main>

    <?php include '../includes/footer.php'; ?>
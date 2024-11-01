<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] != 'admin') {
    header("Location: index.php?error=No+tienes+permisos+de+administrador");
    exit();
}

// Obtener todos los usuarios registrados excepto el admin actual
$result = $conn->query("SELECT id_usuario, nombre, email, rol FROM usuario WHERE id_usuario != {$_SESSION['id_usuario']}");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario = $_POST['id_usuario'];

    // Eliminar usuario
    if (isset($_POST['eliminar_usuario'])) {
        $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();
        header("Location: admin.php?mensaje=Usuario+eliminado+correctamente");
        exit();
    }

    // Crear miembro
    if (isset($_POST['crear_miembro'])) {
        $stmt = $conn->prepare("SELECT * FROM miembro WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            header("Location: admin.php?mensaje=El+usuario+ya+es+miembro");
            exit();
        }
        $stmt->close();

        // Eliminar de monitor si existe
        $stmt = $conn->prepare("DELETE FROM monitor WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        // Crear entrada en la tabla miembro
        $stmt = $conn->prepare("INSERT INTO miembro (id_usuario, fecha_registro, tipo_membresia, entrenamiento) VALUES (?, NOW(), 'Básica', 'General')");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        // Actualizar rol a "miembro" si no lo es ya
        $stmt = $conn->prepare("UPDATE usuario SET rol = 'miembro' WHERE id_usuario = ? AND rol != 'miembro'");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        header("Location: admin.php?mensaje=Miembro+creado+correctamente");
        exit();
    }

    // Crear monitor
    if (isset($_POST['crear_monitor'])) {
        $stmt = $conn->prepare("SELECT * FROM monitor WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            header("Location: admin.php?mensaje=El+usuario+ya+es+monitor");
            exit();
        }
        $stmt->close();

        // Eliminar de miembro si existe
        $stmt = $conn->prepare("DELETE FROM miembro WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        // Crear entrada en la tabla monitor
        $stmt = $conn->prepare("INSERT INTO monitor (id_usuario, especialidad, disponibilidad) VALUES (?, 'General', 'Disponible')");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        // Actualizar rol a "monitor" si no lo es ya
        $stmt = $conn->prepare("UPDATE usuario SET rol = 'monitor' WHERE id_usuario = ? AND rol != 'monitor'");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        header("Location: admin.php?mensaje=Monitor+creado+correctamente");
        exit();
    }

    // Restaurar usuario
    if (isset($_POST['restaurar_usuario'])) {
        $stmt = $conn->prepare("SELECT rol FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($rol_actual);
        $stmt->fetch();
        $stmt->close();

        // Verificar si el rol actual es miembro o monitor
        if ($rol_actual == 'miembro') {
            // Eliminar de la tabla miembro
            $stmt = $conn->prepare("DELETE FROM miembro WHERE id_usuario = ?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();
        } elseif ($rol_actual == 'monitor') {
            // Eliminar de la tabla monitor
            $stmt = $conn->prepare("DELETE FROM monitor WHERE id_usuario = ?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();
        } else {
            header("Location: admin.php?mensaje=El+usuario+ya+es+un+usuario+sin+rol+especial");
            exit();
        }

        // Actualizar rol a "usuario"
        $stmt = $conn->prepare("UPDATE usuario SET rol = 'usuario' WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->close();

        header("Location: admin.php?mensaje=Usuario+restaurado+correctamente");
        exit();
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <!-- Enlace al archivo CSS con los estilos de la página -->
    <link rel="stylesheet" href="/Gimnasio/assets/css/estilos.css">
</head>

<body>
    <!-- Título principal de la sección de administración -->
    <h2>Gestión de Usuarios</h2>

    <!-- Mostrar mensaje de confirmación si existe, recibido como parámetro en la URL -->
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="mensaje-confirmacion">
            <p><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Tabla que muestra la lista de usuarios registrados y sus acciones disponibles -->
    <table>
        <tr>
            <!-- Encabezados de la tabla -->
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <!-- Mostrar el nombre del usuario -->
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>

                <!-- Mostrar el email del usuario -->
                <td><?php echo htmlspecialchars($row['email']); ?></td>

                <!-- Mostrar el rol actual del usuario -->
                <td><?php echo htmlspecialchars($row['rol']); ?></td>

                <!-- Contenedor de acciones disponibles para el usuario (eliminar, asignar como miembro o monitor) -->
                <td>
                    <!-- Formulario para eliminar al usuario -->
                    <form action="admin.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id_usuario" value="<?php echo $row['id_usuario']; ?>">
                        <button type="submit" name="eliminar_usuario" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                            Eliminar
                        </button>
                    </form>

                    <!-- Formulario para asignar el rol de miembro al usuario -->
                    <form action="admin.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id_usuario" value="<?php echo $row['id_usuario']; ?>">
                        <button type="submit" name="crear_miembro">
                            Crear Miembro
                        </button>
                    </form>

                    <!-- Formulario para asignar el rol de monitor al usuario -->
                    <form action="admin.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id_usuario" value="<?php echo $row['id_usuario']; ?>">
                        <button type="submit" name="crear_monitor">
                            Crear Monitor
                        </button>
                    </form>
                    <!-- Formulario para restaurar el rol del usuario a "usuario" -->
                    <form action="admin.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id_usuario" value="<?php echo $row['id_usuario']; ?>">
                        <button type="submit" name="restaurar_usuario">
                            Restaurar Usuario
                        </button>
                    </form>

                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <div class="form_container">
        <form action="logout.php" method="post">
            <button type="submit">Logout</button>
        </form>
    </div>
</body>

</html>
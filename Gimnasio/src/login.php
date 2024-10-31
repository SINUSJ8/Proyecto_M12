<?php
session_start();
require 'db_connection.php'; // Cargar la conexión a la base de datos

// Verificar si el formulario de inicio de sesión fue enviado mediante POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email']; // Capturar el email del formulario
    $contrasenya = $_POST['contrasenya']; // Capturar la contraseña del formulario

    // Preparar una consulta para verificar la existencia del usuario, su contraseña y su rol
    $stmt = $conn->prepare("SELECT id_usuario, contrasenya, rol FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Verificar si el usuario existe en la base de datos
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $hashedPassword, $rol);
        $stmt->fetch();

        // Verificar que la contraseña ingresada coincide con la almacenada
        if (password_verify($contrasenya, $hashedPassword)) {
            // Almacenar en la sesión los datos del usuario autenticado
            $_SESSION['id_usuario'] = $id_usuario;
            $_SESSION['email'] = $email;
            $_SESSION['rol'] = $rol;

            // Nueva consulta para obtener el nombre del usuario y guardarlo en la sesión
            $stmtNombre = $conn->prepare("SELECT nombre FROM usuario WHERE id_usuario = ?");
            $stmtNombre->bind_param("i", $id_usuario);
            $stmtNombre->execute();
            $stmtNombre->bind_result($nombre);
            $stmtNombre->fetch();
            $_SESSION['nombre'] = $nombre; // Guardamos el nombre en la sesión
            $stmtNombre->close();

            // Redirigir a la página correspondiente según el rol del usuario
            switch ($rol) {
                case 'admin':
                    header("Location: /Gimnasio/src/admin.php"); // Redirigir al panel de administración
                    break;
                case 'monitor':
                    header("Location: /Gimnasio/src/monitor.php"); // Redirigir al panel de monitores
                    break;
                case 'miembro':
                    header("Location: /Gimnasio/src/miembro.php"); // Redirigir al área de miembros
                    break;
                default:
                    header("Location: /Gimnasio/src/usuario.php"); // Redirigir al perfil general
                    break;
            }
            exit(); // Terminar el script después de redirigir
        } else {
            // Redirigir al inicio con un mensaje de error si la contraseña es incorrecta
            header("Location: /Gimnasio/index.php?error=Contraseña+incorrecta");
            exit();
        }
    } else {
        // Redirigir al inicio con un mensaje de error si el usuario no existe
        header("Location: /Gimnasio/index.php?error=Usuario+no+encontrado");
        exit();
    }
    $stmt->close(); // Cerrar la declaración preparada
}

$conn->close(); // Cerrar la conexión a la base de datos

<?php

define('BASE_URL', 'http://localhost/Proyecto_M12/Gimnasio/');


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'logout') {
    cerrarSesionUsuario();
}


function obtenerConexion()
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "actividad_02";

    // Crear la conexión
    $conn = new mysqli($servername, $username, $password, $dbname);


    // Verificar si hay errores de conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    return $conn;
}
function redirigirConMensaje($mensaje, $pagina)
{
    // Verifica si la URL ya contiene un "?", en cuyo caso usa "&"
    $separador = strpos($pagina, '?') === false ? '?' : '&';
    header("Location: " . $pagina . $separador . "mensaje=" . urlencode($mensaje));
    exit();
}



function verificarAdmin()
{
    if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'admin') {
        $_SESSION['error'] = "No tienes permisos de administrador.";
        header("Location: index.php");
        exit();
    }

    // No imprimir los mensajes, solo guardarlos para mostrarlos en el body
    if (isset($_SESSION['error'])) {
        $_SESSION['mensaje_error'] = $_SESSION['error'];
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['mensaje'])) {
        $_SESSION['mensaje_confirmacion'] = $_SESSION['mensaje'];
        unset($_SESSION['mensaje']);
    }
}


function iniciarSesionUsuario($email, $contrasenya)
{
    $conn = obtenerConexion();

    $_SESSION['form_data'] = ['email' => $email]; // Guardar el email en la sesión

    $stmt = $conn->prepare("SELECT id_usuario, contrasenya, rol FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $hashedPassword, $rol);
        $stmt->fetch();

        if (password_verify($contrasenya, $hashedPassword)) {
            $_SESSION['id_usuario'] = $id_usuario;
            $_SESSION['email'] = $email;
            $_SESSION['rol'] = $rol;

            $stmtNombre = $conn->prepare("SELECT nombre FROM usuario WHERE id_usuario = ?");
            $stmtNombre->bind_param("i", $id_usuario);
            $stmtNombre->execute();
            $stmtNombre->bind_result($nombre);
            $stmtNombre->fetch();
            $_SESSION['nombre'] = $nombre;
            $stmtNombre->close();

            // Limpiar los datos del formulario en la sesión al iniciar sesión correctamente
            unset($_SESSION['form_data']);

            // Redireccionar según el rol del usuario
            switch ($rol) {
                case 'admin':
                    header("Location: ../admin/admin.php");
                    break;
                case 'monitor':
                    header("Location: ../monitores/monitor.php");
                    break;
                case 'miembro':
                    header("Location: ../miembros/miembro.php");
                    break;
                default:
                    header("Location: ../usuarios/usuario.php");
                    break;
            }
            exit();
        } else {
            $_SESSION['error'] = "Contraseña incorrecta";
            header("Location: ../auth/log.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Usuario no encontrado";
        header("Location: ../auth/log.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
function cerrarSesionUsuario()
{
    session_start();
    session_unset();
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

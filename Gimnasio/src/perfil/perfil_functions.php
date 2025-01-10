<?php
function actualizarUsuario($conn, $id_usuario, $nombre, $email, $telefono, $password)
{
    try {
        // Actualizar el usuario, contraseña opcional
        $query = "UPDATE usuario SET nombre = ?, email = ?, telefono = ?";
        if (!empty($password)) {
            $query .= ", contrasenya = ?";
        }
        $query .= " WHERE id_usuario = ?";
        $stmt = $conn->prepare($query);

        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt->bind_param("ssssi", $nombre, $email, $telefono, $passwordHash, $id_usuario);
        } else {
            $stmt->bind_param("sssi", $nombre, $email, $telefono, $id_usuario);
        }

        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}
function obtenerDetalleUsuario($conn, $id_usuario)
{
    $stmt = $conn->prepare("SELECT nombre, email, telefono FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc(); // Devuelve un array asociativo o null
    $stmt->close();

    return $usuario;
}

function verificarSesion()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start(); // Iniciar sesión solo si no está activa
    }

    if (!isset($_SESSION['id_usuario'])) {
        // Redirigir al login si no está autenticado
        header('Location: ../../login.php');
        exit();
    }
}

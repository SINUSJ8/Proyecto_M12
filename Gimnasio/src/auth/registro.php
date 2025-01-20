<?php
session_start();
require_once('../includes/general.php');
$conn = obtenerConexion();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($conn->real_escape_string($_POST['nombre']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $contrasenya = trim($_POST['contrasenya']);
    $confirmarContrasenya = trim($_POST['confirmar_contrasenya']);

    $_SESSION['form_data'] = $_POST;  // Guarda los datos en la sesión

    // Validaciones de servidor
    if (empty($nombre) || empty($email) || empty($contrasenya) || empty($confirmarContrasenya)) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
        header("Location: reg.php");
        exit();
    }

    if (!preg_match('/[a-zA-Z]/', $nombre)) {
        $_SESSION['error'] = "El nombre debe contener al menos una letra.";
        header("Location: reg.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "El correo electrónico no tiene un formato válido.";
        header("Location: reg.php");
        exit();
    }

    if (strlen($contrasenya) < 6) {
        $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres.";
        header("Location: reg.php");
        exit();
    }

    if ($contrasenya !== $confirmarContrasenya) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        header("Location: reg.php");
        exit();
    }

    $contrasenyaHash = password_hash($contrasenya, PASSWORD_DEFAULT);

    // Verificar si el correo ya está registrado
    $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Error en el servidor. Inténtalo más tarde.";
        header("Location: reg.php");
        exit();
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        error_log("Error en la consulta: " . $stmt->error, 3, "../logs/error.log");
        $_SESSION['error'] = "Error en el servidor. Inténtalo más tarde.";
        header("Location: reg.php");
        exit();
    }

    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "El correo electrónico ya está registrado.";
        header("Location: reg.php");
        exit();
    }

    $stmt->close();

    // Inserción en la base de datos
    $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, contrasenya) VALUES (?, ?, ?)");
    if (!$stmt) {
        $_SESSION['error'] = "Error en el servidor. Inténtalo más tarde.";
        header("Location: reg.php");
        exit();
    }

    $stmt->bind_param("sss", $nombre, $email, $contrasenyaHash);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Registro exitoso.";
        unset($_SESSION['form_data']);  // Limpia los datos si el registro es exitoso
        header("Location: reg.php");
    } else {
        error_log("Error al registrar: " . $stmt->error, 3, "../logs/error.log");
        $_SESSION['error'] = "Error al registrarse.";
        header("Location: reg.php");
    }

    $stmt->close();
}

$conn->close();

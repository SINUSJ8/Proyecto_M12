<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "actividad_02";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar si hay errores de conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Procesar el formulario solo si el método de solicitud es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escapar los datos de entrada para evitar inyecciones SQL
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $email = $conn->real_escape_string($_POST['email']);
    $contrasenya = $_POST['contrasenya'];

    // Validación de longitud de la contraseña en el servidor
    if (strlen($contrasenya) < 6) {
        // Redirigir con mensaje de error si la contraseña es demasiado corta
        header("Location: /Gimnasio/index.php?error=La+contraseña+debe+tener+al+menos+6+caracteres");
        exit();
    }

    // Encriptar la contraseña después de pasar la validación de longitud
    $contrasenyaHash = password_hash($contrasenya, PASSWORD_DEFAULT);

    // Verificar si el correo electrónico ya está registrado
    $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Redirigir con mensaje de error si el correo ya está registrado
        header("Location: /Gimnasio/index.php?error=El+correo+electrónico+ya+está+registrado");
        exit();
    }

    $stmt->close();

    // Preparar la inserción del nuevo usuario en la base de datos
    $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, contrasenya) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $email, $contrasenyaHash);

    if ($stmt->execute()) {
        // Redirigir a la página de inicio con un mensaje de registro exitoso
        header("Location: /Gimnasio/index.php?mensaje=Registro+exitoso");
    } else {
        // Redirigir con mensaje de error si la inserción falla
        header("Location: /Gimnasio/index.php?error=Error+al+registrarse");
    }

    $stmt->close(); // Cerrar la declaración preparada
}

$conn->close(); // Cerrar la conexión a la base de datos

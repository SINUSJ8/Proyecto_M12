<?php
// Iniciar sesión solo si no ha sido iniciada previamente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica que el usuario ha iniciado sesión y tiene el rol de "miembro"
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] != 'usuario') {
    header("Location: index.php?error=Acceso+denegado");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : "Mi Gimnasio"; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <script src="../../assets/js/validacion.js"></script>
</head>

<body>
    <header>
        <div class="header-content">
            <h1>FitBay</h1>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        </div>

        <nav id="navegacion-rapida">
            <a href="usuario.php">Mi Perfil</a>
            <a href="user_membresias.php">Elige tu Membresía</a>
            <a href="user_notificaciones.php">Mis Notificaciones</a>
            <form action="../includes/general.php" method="post" style="display: inline;">
                <input type="hidden" name="accion" value="logout">
                <button type="submit" class="logout-link">Cerrar Sesión</button>
            </form>
        </nav>

    </header>
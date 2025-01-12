<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario es monitor
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'monitor') {
    header("Location: ../index.php?error=Acceso+denegado");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : "Panel del Monitor"; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <script src="../../assets/js/validacion.js"></script>
</head>

<body>
    <header>
        <div class="header-content">
            <h1>Panel del Monitor</h1>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        </div>
        <nav id="navegacion-rapida">
            <a href="../monitores/monitor.php">Perfil</a>
            <a href="../clases/clases_monitor.php">Mis Clases</a>
            <a href="../monitores/notificaciones_monitor.php">Mis Notificaciones</a>
            <form action="../includes/general.php" method="post" style="display: inline;">
                <input type="hidden" name="accion" value="logout">
                <button type="submit" class="logout-link">Cerrar Sesión</button>
            </form>
        </nav>
    </header>
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
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">

    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <script src="../../assets/js/validacion.js"></script>
</head>

<body>
    <header>
        <div class="header-content">
            <h1>Panel del Monitor</h1>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        </div>
        <nav class="navbar navbar-expand-lg" style="background-color: #3aafa9;">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                    <ul class="navbar-nav flex-wrap">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../monitores/monitor.php">Perfil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../clases/clases_monitor.php">Mis Clases</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../monitores/calendario_monitor.php">Mi Calendario</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../monitores/notificaciones_monitor.php">Mis Notificaciones</a>
                        </li>
                        <li class="nav-item">
                            <form action="../includes/general.php" method="post" style="display: inline;">
                                <input type="hidden" name="accion" value="logout">
                                <button type="submit" class="btn btn-sm text-white" style="background-color: #dc3545;">Cerrar Sesión</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
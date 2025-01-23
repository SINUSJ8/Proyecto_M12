<?php
// Iniciar sesión solo si no ha sido iniciada previamente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica que el usuario ha iniciado sesión y tiene el rol de "miembro"
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] != 'miembro') {
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
            <h1>Mi Gimnasio</h1>
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
                            <a class="nav-link text-white" href="../clases/clases_disponibles.php">Clases Disponibles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../clases/mis_clases.php">Mis Clases</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../miembros/mi_calendario.php">Mi Calendario</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../membresias/mi_membresia.php">Mi Membresía</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../miembros/miembro.php">Mi Perfil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../miembros/miembro_pagos.php">Historial de Pagos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../miembros/mis_notificaciones.php">Mis Notificaciones</a>
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
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
    <?php if (!empty($cssFile)): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($cssFile); ?>">
    <?php endif; ?>
    <script src="../../assets/js/validacion.js"></script>
</head>

<body>
    <header class="adminHeader">
        <h1>Panel de Administración</h1>
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        <nav id="navegacion-rapida-admin">
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <ul class="menu-horizontal">
                    <li><a href="../admin/admin.php">Panel Principal</a></li>

                    <li>
                        <a href="../usuarios/usuarios.php">Usuarios</a>
                        <ul class="menu-vertical">
                            <li><a href="../usuarios/crear_usuario.php">Crear usuario</a></li>
                        </ul>
                    </li>

                    <li><a href="../miembros/miembros.php">Miembros</a></li>
                    <li><a href="../monitores/monitores.php">Monitores</a></li>

                    <li>
                        <a href="../clases/clases.php">Clases</a>
                        <ul class="menu-vertical">
                            <li><a href="../clases/crear_clase.php">Crear clase</a></li>
                        </ul>
                    </li>

                    <li>
                        <a href="../membresias/membresias.php">Membresías</a>
                        <ul class="menu-vertical">
                            <li><a href="../membresias/crear_membresia.php">Crear o Editar Membresía</a></li>
                            <li><a href="../membresias/planes.php">Planes</a></li>
                        </ul>
                    </li>

                    <li><a href="../admin/especialidades.php">Especialidades</a></li>

                    <li>
                        <a href="../admin/notificaciones.php">Notificaciones</a>
                        <ul class="menu-vertical">
                            <li><a href="../admin/notificaciones_enviadas.php">Enviadas</a></li>
                        </ul>
                    </li>

                    <li><a href="../admin/historial_pagos.php">Historial de pagos</a></li>
                    <li>
                        <form action="../includes/general.php" method="post" style="display: inline;">
                            <input type="hidden" name="accion" value="logout">
                            <button type="submit" class="logout-admin">Cerrar Sesión</button>
                        </form>
                    </li>

                </ul>
            <?php endif; ?>


        </nav>
    </header>
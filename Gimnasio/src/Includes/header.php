<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gimnasio</title>
        <title><?php echo isset($title) ? $title : "Mi Gimnasio"; ?></title>
        <link rel="stylesheet" href="../../assets/css/estilos.css">
        <script src="../../assets/js/validacion.js"></script>
    </head>
    <body>
        <header>
            <div class="header-content">
                <h1>Gimnasio</h1>
            </div>
            <nav id="navegacion-rapida">
                <a href="src/clases/clases_disponibles.php">Oferta de Clases</a>
                <a href="src/usuarios/user_membresias.php">Membresías</a>
                <a href="src/inicio/contacto.php">Contacto</a>
                <a href="src/inicio/about.php">Acerca de</a>
                <form action="../includes/general.php" method="post" style="display: inline;">
                </form>
            </nav>
        </header>
    </body>
</html>
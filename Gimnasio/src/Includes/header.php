<?php require_once(__DIR__ . '/../includes/general.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : "Mi Gimnasio"; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/estilos.css">
    <script src="<?php echo BASE_URL; ?>assets/js/validacion.js"></script>
</head>

<body>
    <header>
        <div class="header-content">
            <a href= "<?php echo BASE_URL; ?>index.php">
                <img src="<?php echo BASE_URL; ?>assets/imgs/FitBay.png" alt="FitBay" class="logo" width="20%">
            </a>
        </div>
        <nav id="navegacion-rapida">
            <a href="<?php echo BASE_URL; ?>src/clases/clases_disponibles.php">Oferta de Clases</a>
            <a href="<?php echo BASE_URL; ?>src/membresias/membresias_publicas.php">Membresías</a>
            <a href="<?php echo BASE_URL; ?>src/inicio/contacto.php">Contacto</a>
            <a href="<?php echo BASE_URL; ?>src/inicio/about.php">Acerca de</a>
            <a href="<?php echo BASE_URL; ?>src/auth/log.php">
                <img src="<?php echo BASE_URL; ?>assets/imgs/icono.png" alt="FitBay" class="logo" width="3%">
            </a>
        </nav>
    </header>
</body>

</html>
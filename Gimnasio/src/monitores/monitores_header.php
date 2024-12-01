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
    <header>
        <h1>Panel de Monitor</h1>
        <nav id="navegacion-rapida">
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <a href="../clases/clases.php">Clases</a>
            <?php endif; ?>

            <form action="../includes/general.php" method="post" style="display: inline;">
                <input type="hidden" name="accion" value="logout">
                <button type="submit" class="logout-link">Cerrar Sesión</button>
            </form>
        </nav>
    </header>
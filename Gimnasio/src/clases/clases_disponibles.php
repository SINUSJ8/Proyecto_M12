<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>


<?php
require_once __DIR__ . '/../includes/general.php';
$title = "Clases Disponibles";

// Incluir el header basado en el rol del usuario
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'miembro') {
    include_once __DIR__ . '/../miembros/miembro_header.php';
} else {
    include_once __DIR__ . '/../includes/header.php';
}
?>
</head>
<body>
    

<main>
    <h1 class="section-title">Clases Disponibles</h1>
    <p class="intro-text">¡Únete a nuestras clases y pasa un buen rato mientras te mantienes en forma y logras tus objetivos!</p>

    <div class="clases-grid">
        <!-- Clase Cardio -->
        <div class="clase-card">
            <h2>Cardio</h2>
            <img src="../../assets/imgs/cardio.webp" alt="Clase de Cardio" class="clase-image">
            <p>Fortalece tu corazón y mejora tu resistencia física con nuestras dinámicas sesiones de cardio.</p>
            <ul class="clase-benefits">
                <li>Quema calorías rápidamente.</li>
                <li>Mejora la salud cardiovascular.</li>
                <li>Aumenta tu capacidad pulmonar.</li>
            </ul>
            <!-- Botón apuntarme -->
            <?php
            // Es miembro?
            if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'miembro') {
                ?>
                <div class="button-container">
                    <a href="<?php echo BASE_URL; ?>src/auth/reg.php" class="button">¡Apúntame!</a>
                </div>
                <?php
            } else {
                ?>
                <div class="button-container">
                    <a href="<?php echo BASE_URL; ?>src/clases/mis_clases.php" class="button">¡Apúntame!</a>
                </div>
                <?php
            }
            ?>
            
        </div>
        
        

        <!-- Clase Pesas -->
        <div class="clase-card">
            <h2>Pesas</h2>
            <img src="../../assets/imgs/pesas.avif" alt="Clase de Pesas" class="clase-image">
            <p>Desarrolla tu fuerza muscular y mejora tu resistencia con nuestras sesiones de entrenamiento con pesas.</p>
            <ul class="clase-benefits">
                <li>Aumenta la masa muscular.</li>
                <li>Fortalece la densidad ósea.</li>
                <li>Mejora el metabolismo.</li>
            </ul>
            <!-- Botón apuntarme -->
            <div class="button-container">
                <a href="<?php echo BASE_URL; ?>src/auth/reg.php" class="button">¡Apúntame!</a>
            </div>
        </div>

        <!-- Clase Pilates -->
        <div class="clase-card">
            <h2>Pilates</h2>
            <img src="../../assets/imgs/pilates.jpg" alt="Clase de Pilates" class="clase-image">
            <p>Un entrenamiento suave que mejora la flexibilidad, la musculatura profunda y alivia el estrés.</p>
            <ul class="clase-benefits">
                <li>Mejora la postura.</li>
                <li>Reduce el estrés.</li>
                <li>Aumenta la flexibilidad y la movilidad.</li>
            </ul>
            <!-- Botón apuntarme -->
            <div class="button-container">
                <a href="<?php echo BASE_URL; ?>src/auth/reg.php" class="button">¡Apúntame!</a>
            </div>
        </div>

        <!-- Clase Yoga -->
        <div class="clase-card">
            <h2>Yoga</h2>
            <img src="../../assets/imgs/yoga.jpg" alt="Clase de Yoga" class="clase-image">
            <p>Encuentra paz y equilibrio con nuestras clases de yoga, diseñadas para todos los niveles.</p>
            <ul class="clase-benefits">
                <li>Aumenta la concentración.</li>
                <li>Mejora la flexibilidad y el equilibrio.</li>
                <li>Reduce el estrés y la ansiedad.</li>
            </ul>
            <!-- Botón apuntarme -->
            <div class="button-container">
                <a href="<?php echo BASE_URL; ?>src/auth/reg.php" class="button">¡Apúntame!</a>
            </div>
        </div>

        <!-- Entrenamiento Funcional -->
        <div class="clase-card">
            <h2>Entrenamiento Funcional</h2>
            <img src="../../assets/imgs/entrenamiento_funcional.jpg" alt="Entrenamiento Funcional" class="clase-image">
            <p>Ejercicios prácticos que mejoran tu fuerza y coordinación para las actividades diarias.</p>
            <ul class="clase-benefits">
                <li>Mejora el rendimiento en actividades cotidianas.</li>
                <li>Fortalece grupos musculares clave.</li>
                <li>Desarrolla agilidad y coordinación.</li>
            </ul>
            <!-- Botón apuntarme -->
            <div class="button-container">
                <a href="<?php echo BASE_URL; ?>src/auth/reg.php" class="button">¡Apúntame!</a>
            </div>
        </div>
    </div>

    <!-- Botón Volver -->
    <div class="button-container">
        <a href="<?php echo BASE_URL; ?>index.php" class="button">Volver a la Página Principal</a>
    </div>
</main>
</body>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</html>
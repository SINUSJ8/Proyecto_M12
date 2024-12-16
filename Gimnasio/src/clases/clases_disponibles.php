<?php
$title = "Clases Disponibles";
include '../miembros/miembro_header.php';
?>

<head>
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>

<main class="form_container">
    <h1>Clases Disponibles</h1>
    <p>¡Únete a nuestras clases y pasa un buen rato mientras te mantienes en forma y logras tus objetivos!</p>

    <div class="clases-container">
        <div class="clase-card">
            <h2>Cardio</h2>
            <img src="../../assets/imgs/cardio.webp" alt="Clase de Cardio">
            <p>Fortalece tu corazón y mejora tu resistencia física con nuestras dinámicas sesiones de cardio.</p>
            <ul>
                <li>Quema calorías rápidamente.</li>
                <li>Mejora la salud cardiovascular.</li>
                <li>Aumenta tu capacidad pulmonar.</li>
            </ul>
        </div>

        <div class="clase-card">
            <h2>Pesas</h2>
            <img src="../../assets/imgs/pesas.avif" alt="Clase de Pesas">
            <p>Desarrolla tu fuerza muscular y mejora tu resistencia con nuestras sesiones de entrenamiento con pesas.</p>
            <ul>
                <li>Aumenta la masa muscular.</li>
                <li>Fortalece la densidad ósea.</li>
                <li>Mejora el metabolismo.</li>
            </ul>
        </div>

        <div class="clase-card">
            <h2>Pilates</h2>
            <img src="../../assets/imgs/pilates.jpg"  alt="Clase de Pilates">
            <p>Un entrenamiento suave que mejora la flexibilidad, la musculatura profunda y alivia el estrés.</p>
            <ul>
                <li>Mejora la postura.</li>
                <li>Reduce el estrés</li>
                <li>Aumenta la flexibilidad y la movilidad.</li>
            </ul>
        </div>

        <div class="clase-card">
            <h2>Yoga</h2>
            <img src="../../assets/imgs/yoga.jpg"  alt="Clase de Yoga">
            <p>Encuentra paz y equilibrio con nuestras clases de yoga, diseñadas para todos los niveles.</p>
            <ul>
                <li>Aumenta la concentración.</li>
                <li>Mejora la flexibilidad y el equilibrio.</li>
                <li>Reduce el estrés y la ansiedad.</li>
            </ul>
        </div>

        <div class="clase-card">
            <h2>Entrenamiento Funcional</h2>
            <img src="../../assets/imgs/entrenamiento_funcional.jpg" alt="Entrenamiento Funcional">
            <p>Ejercicios prácticos que mejoran tu fuerza y coordinación para las actividades diarias.</p>
            <ul>
                <li>Mejora el rendimiento en actividades cotidianas</li>
                <li>Fortalece grupos musculares clave.</li>
                <li>Desarrolla agilidad y coordinación.</li>
            </ul>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
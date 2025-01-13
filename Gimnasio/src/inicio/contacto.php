<?php
require_once __DIR__ . '/../includes/general.php';
$title = "Contacto";

// Incluir el header basado en el rol del usuario
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'miembro') {
    include_once __DIR__ . '/../miembros/miembro_header.php';
} else {
    include_once __DIR__ . '/../includes/header.php';
}
?>

<main class="form_container">
    <h1 class="section-title">Formulario de contacto</h1>
    <p class="intro-text">Envíanos tu consulta y te responderemos lo antes posible.</p>

    <form action="formulario.php" method="POST" onsubmit="return validarFormulario()">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono">
        <div class="notificacion-mensaje">
            <textarea id="descripcion" name="descripcion"  placeholder="Tu consulta" required></textarea>
        </div>
        <div class="checkbox-container">
            <input type="checkbox" id="condiciones" name="condiciones" required>
            <label for="condiciones">Quiero recibir la Newsletter y acepto los términos y condiciones.</label>
        </div>

        <button type="submit" class="btn-general">Enviar</button>
    </form>

    <!-- Botón Volver -->
    <div class="button-container">
        <a href="<?php echo BASE_URL; ?>index.php" class="button">Volver al inicio</a>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
<?php
$title = "Mis Clases";
include '../miembros/miembro_header.php';
require_once '../clases/mi_clase_functions.php';

$conn = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];

try {
    $id_miembro = obtenerIdMiembro($conn, $id_usuario);

    // Obtener las clases a las que el miembro está inscrito
    $clasesInscritas = obtenerClasesInscritas($conn, $id_miembro);

    // Procesar formulario para apuntarse o borrarse
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_clase = intval($_POST['id_clase']);
        $accion = $_POST['accion'];

        if ($accion === 'apuntarse') {
            if (claseEstaCompleta($conn, $id_clase)) {
                $mensaje = 'completa';
            } elseif (yaInscritoEnClase($conn, $id_clase, $id_miembro)) {
                $mensaje = 'ya_inscrito';
            } else {
                $mensaje = apuntarseClase($conn, $id_clase, $id_miembro) ? 'apuntado' : 'error';
            }
        } elseif ($accion === 'borrarse') {
            $mensaje = borrarseClase($conn, $id_clase, $id_miembro) ? 'borrado' : 'no_borrado';
        }

        header("Location: mis_clases.php?mensaje=$mensaje");
        exit;
    }

    // Obtener las especialidades del miembro
    $especialidades = obtenerEspecialidadesMiembro($conn, $id_miembro);

    // Obtener las clases disponibles según las especialidades
    $clasesDisponibles = !empty($especialidades)
        ? obtenerClasesDisponibles($conn, $especialidades, $id_miembro)
        : [];
} catch (Exception $e) {
    echo "<p class='mensaje-error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>

<main class="form_container">
    <h1 class="section-title">Mis Clases</h1>

    <?php if (isset($_GET['mensaje'])): ?>
        <p class="mensaje-confirmacion">
            <?php if ($_GET['mensaje'] === 'apuntado'): ?>
                ¡Te has inscrito correctamente en la clase!
            <?php elseif ($_GET['mensaje'] === 'ya_inscrito'): ?>
                Ya estás inscrito en esta clase.
            <?php elseif ($_GET['mensaje'] === 'borrado'): ?>
                Te has dado de baja de la clase correctamente.
            <?php elseif ($_GET['mensaje'] === 'no_borrado'): ?>
                No se pudo borrar tu inscripción. Inténtalo de nuevo.
            <?php elseif ($_GET['mensaje'] === 'completa'): ?>
                La clase está completa. No puedes inscribirte.
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <!-- Clases Inscritas -->
    <?php if (!empty($clasesInscritas)): ?>
        <h2>Clases Inscritas</h2>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Nombre de la Clase</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clasesInscritas as $claseInscrita): ?>
                    <tr>
                        <td><?= htmlspecialchars($claseInscrita['nombre']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id_clase" value="<?= htmlspecialchars($claseInscrita['id_clase']); ?>">
                                <input type="hidden" name="accion" value="borrarse">
                                <button type="submit" class="btn-general btn-danger">Borrarme</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="mensaje-info">No estás inscrito en ninguna clase.</p>
    <?php endif; ?>

    <!-- Clases Disponibles -->
    <?php if (!empty($clasesDisponibles)): ?>
        <h2>Clases Disponibles</h2>
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Nombre de la Clase</th>
                    <th>Especialidad</th>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Duración</th>
                    <th>Capacidad Máxima</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clasesDisponibles as $clase): ?>
                    <tr>
                        <td><?= htmlspecialchars($clase['nombre']); ?></td>
                        <td><?= htmlspecialchars($clase['especialidad']); ?></td>
                        <td><?= htmlspecialchars($clase['fecha']); ?></td>
                        <td><?= htmlspecialchars($clase['horario']); ?></td>
                        <td><?= htmlspecialchars($clase['duracion']); ?> minutos</td>
                        <td><?= htmlspecialchars($clase['capacidad_maxima']); ?></td>
                        <td>
                            <?php if ($clase['inscrito']): ?>
                                <span style="color: green;">Ya inscrito</span>
                            <?php elseif ($clase['completa']): ?>
                                <span style="color: red;">Completa</span>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="id_clase" value="<?= htmlspecialchars($clase['id_clase']); ?>">
                                    <input type="hidden" name="accion" value="apuntarse">
                                    <button type="submit" class="btn-general">Apuntarme</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="mensaje-info">No hay clases disponibles para tus especialidades.</p>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
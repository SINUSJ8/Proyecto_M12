<?php
$title = "Mis Clases";
include '../miembros/miembro_header.php';
require_once '../clases/mi_clase_functions.php';

$conn = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];
$id_especialidad = isset($_GET['especialidad']) ? intval($_GET['especialidad']) : null;

// Configuración de paginación
$page_inscritas = isset($_GET['page_inscritas']) ? max(1, intval($_GET['page_inscritas'])) : 1;
$page_disponibles = isset($_GET['page_disponibles']) ? max(1, intval($_GET['page_disponibles'])) : 1;
$per_page = 5; // Número de clases por página
$offset_inscritas = ($page_inscritas - 1) * $per_page;
$offset_disponibles = ($page_disponibles - 1) * $per_page;

try {
    // Obtener el ID del miembro
    $id_miembro = obtenerIdMiembro($conn, $id_usuario);

    // Obtener especialidades del miembro
    $especialidades = obtenerEspecialidadesMiembro($conn, $id_miembro);

    // Asegurar que $especialidades sea un array
    if (empty($especialidades)) {
        $especialidades = [];
    }

    // Obtener el total de clases inscritas y disponibles
    $total_clases_inscritas = contarClasesInscritas($conn, $id_miembro);
    $total_clases_disponibles = contarClasesDisponibles($conn, $especialidades, $id_miembro);

    // Calcular el total de páginas
    $total_pages_inscritas = ceil($total_clases_inscritas / $per_page);
    $total_pages_disponibles = ceil($total_clases_disponibles / $per_page);

    // Obtener las clases inscritas y disponibles
    $clasesInscritas = obtenerClasesInscritas($conn, $id_miembro, $per_page, $offset_inscritas);
    $clasesDisponibles = obtenerClasesDisponiblesPaginadas($conn, $especialidades, $id_miembro, $per_page, $offset_disponibles);

    // Manejo de acciones (apuntarse o borrarse)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_clase = intval($_POST['id_clase']);
        $accion = $_POST['accion'];

        if ($accion === 'apuntarse') {
            if (claseEstaCompleta($conn, $id_clase)) {
                $mensaje = 'La clase está completa. No puedes inscribirte.';
            } elseif (yaInscritoEnClase($conn, $id_clase, $id_miembro)) {
                $mensaje = 'Ya estás inscrito en esta clase.';
            } else {
                $resultado = apuntarseClase($conn, $id_clase, $id_miembro);
                if ($resultado) {
                    $mensaje = '¡Te has inscrito correctamente!';
                } else {
                    $mensaje = 'Error al inscribirte en la clase.';
                }
            }
        } elseif ($accion === 'borrarse') {
            $resultado = borrarseClase($conn, $id_clase, $id_miembro);
            if ($resultado) {
                $mensaje = 'Te has dado de baja correctamente.';
            } else {
                $mensaje = 'Error al darte de baja.';
            }
        }

        // Recalcular las clases y la paginación después de la acción
        $total_clases_inscritas = contarClasesInscritas($conn, $id_miembro);
        $total_pages_inscritas = ceil($total_clases_inscritas / $per_page);
        if ($page_inscritas > $total_pages_inscritas) {
            $page_inscritas = max(1, $total_pages_inscritas);
            $offset_inscritas = ($page_inscritas - 1) * $per_page;
        }
        $clasesInscritas = obtenerClasesInscritas($conn, $id_miembro, $per_page, $offset_inscritas);

        $total_clases_disponibles = contarClasesDisponibles($conn, $especialidades, $id_miembro);
        $total_pages_disponibles = ceil($total_clases_disponibles / $per_page);
        if ($page_disponibles > $total_pages_disponibles) {
            $page_disponibles = max(1, $total_pages_disponibles);
            $offset_disponibles = ($page_disponibles - 1) * $per_page;
        }
        $clasesDisponibles = obtenerClasesDisponiblesPaginadas($conn, $especialidades, $id_miembro, $per_page, $offset_disponibles);
    }
} catch (Exception $e) {
    echo "<p class='mensaje-error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>



<main>
    <h1 class="section-title">Mis Clases</h1>

    <?php if (!empty($mensaje)): ?>
        <p class="mensaje-confirmacion"><?= htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <h2 class="intro-text">Clases Inscritas</h2>
    <?php if (!empty($clasesInscritas)): ?>
        <table class="styled-table mis-clases">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clasesInscritas as $clase): ?>
                    <tr>
                        <td><?= htmlspecialchars($clase['nombre']); ?></td>
                        <td><?= htmlspecialchars($clase['fecha']); ?></td>
                        <td><?= htmlspecialchars($clase['horario']); ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="id_clase" value="<?= htmlspecialchars($clase['id_clase']); ?>">
                                <input type="hidden" name="accion" value="borrarse">
                                <button type="submit" class="delete-button">Borrarme</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <!-- Añadir filas vacías para mantener el tamaño -->
                <?php for ($i = count($clasesInscritas); $i < $per_page; $i++): ?>
                    <tr class="fila-vacia">
                        <td colspan="4">&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page_inscritas > 1): ?>
                <a href="?page_inscritas=<?= $page_inscritas - 1; ?>&page_disponibles=<?= $page_disponibles; ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages_inscritas; $i++): ?>
                <a href="?page_inscritas=<?= $i; ?>&page_disponibles=<?= $page_disponibles; ?>" class="btn-general <?= $i === $page_inscritas ? 'active' : ''; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page_inscritas < $total_pages_inscritas): ?>
                <a href="?page_inscritas=<?= $page_inscritas + 1; ?>&page_disponibles=<?= $page_disponibles; ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="mensaje-info">No estás inscrito en ninguna clase.</p>
    <?php endif; ?>


    <h2 class="intro-text">Clases Disponibles</h2>
    <?php if (!empty($clasesDisponibles)): ?>
        <table class="styled-table mis-clases">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Especialidad</th>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Duración</th>
                    <th>Capacidad</th>
                    <th>Monitor</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clasesDisponibles as $clase): ?>
                    <tr>
                        <td><?= htmlspecialchars($clase['nombre']); ?></td>
                        <td><?= htmlspecialchars($clase['especialidad']); ?></td>
                        <td><?= date('d/m/Y', strtotime($clase['fecha'])); ?></td>
                        <td><?= htmlspecialchars($clase['horario']); ?></td>
                        <td><?= htmlspecialchars($clase['duracion']); ?> minutos</td>
                        <td><?= htmlspecialchars($clase['capacidad_maxima']); ?></td>
                        <td><?= htmlspecialchars($clase['monitor'] ?? ''); ?></td>
                        <td>
                            <?php if ($clase['estado'] === 'inscrito'): ?>
                                <span class="mensaje-inscrito">Ya inscrito</span>
                            <?php elseif ($clase['estado'] === 'completa'): ?>
                                <span class="mensaje-completa">Completa</span>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="id_clase" value="<?= htmlspecialchars($clase['id_clase']); ?>">
                                    <input type="hidden" name="accion" value="apuntarse">
                                    <button type="submit" class="btn-general">Apuntarme</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <!-- Añadir filas vacías para mantener el tamaño -->
                <?php for ($i = count($clasesDisponibles); $i < $per_page; $i++): ?>
                    <tr class="fila-vacia">
                        <td colspan="4">&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <div class="pagination">
            <?php if ($page_disponibles > 1): ?>
                <a href="?page_disponibles=<?= $page_disponibles - 1; ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages_disponibles; $i++): ?>
                <a href="?page_disponibles=<?= $i; ?>" class="btn-general <?= $i === $page_disponibles ? 'active' : ''; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page_disponibles < $total_pages_disponibles): ?>
                <a href="?page_disponibles=<?= $page_disponibles + 1; ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="mensaje-info">No hay clases disponibles para tus especialidades.</p>
    <?php endif; ?>


</main>

<?php include '../includes/footer.php'; ?>
<script src="../../assets/js/clases.js"></script>
<script src="../../assets/js/alertas.js"></script>
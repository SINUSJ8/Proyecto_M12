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
    $id_miembro = obtenerIdMiembro($conn, $id_usuario);

    if (!$id_especialidad) {
        $stmt = $conn->prepare("SELECT id_especialidad FROM miembro_entrenamiento WHERE id_miembro = ?");
        $stmt->bind_param("i", $id_miembro);
        $stmt->execute();
        $stmt->bind_result($id_especialidad);
        $stmt->fetch();
        $stmt->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_clase = intval($_POST['id_clase']);
        $accion = $_POST['accion'];

        if ($accion === 'apuntarse') {
            if (claseEstaCompleta($conn, $id_clase)) {
                $mensaje = 'La clase está completa. No puedes inscribirte.';
            } elseif (yaInscritoEnClase($conn, $id_clase, $id_miembro)) {
                $mensaje = 'Ya estás inscrito en esta clase.';
            } else {
                $mensaje = apuntarseClase($conn, $id_clase, $id_miembro) ? '¡Te has inscrito correctamente!' : 'Error al inscribirte.';
            }
        } elseif ($accion === 'borrarse') {
            $mensaje = borrarseClase($conn, $id_clase, $id_miembro) ? 'Te has dado de baja correctamente.' : 'Error al darte de baja.';
        }
    }

    $especialidades = obtenerEspecialidadesMiembro($conn, $id_miembro);
    $total_clases_inscritas = contarClasesInscritas($conn, $id_miembro);
    $total_clases_disponibles = contarClasesDisponibles($conn, $especialidades, $id_miembro);

    $total_pages_inscritas = ceil($total_clases_inscritas / $per_page);
    $total_pages_disponibles = ceil($total_clases_disponibles / $per_page);

    $clasesInscritas = obtenerClasesInscritas($conn, $id_miembro, $per_page, $offset_inscritas);
    $clasesDisponibles = obtenerClasesDisponiblesPaginadas($conn, $especialidades, $id_miembro, $per_page, $offset_disponibles);
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
                                <button type="submit" class="btn-general delete-button">Borrarme</button>
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
                        <td><?= htmlspecialchars($clase['monitor'] ?? ''); ?></td>
                        <td>
                            <?php if ($clase['inscrito']): ?>
                                <span class="mensaje-inscrito">Ya inscrito</span>
                            <?php elseif ($clase['completa']): ?>
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
                        <td colspan="8">&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page_disponibles > 1): ?>
                <a href="?page_disponibles=<?= $page_disponibles - 1; ?>&page_inscritas=<?= $page_inscritas; ?>" class="btn-general">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages_disponibles; $i++): ?>
                <a href="?page_disponibles=<?= $i; ?>&page_inscritas=<?= $page_inscritas; ?>" class="btn-general <?= $i === $page_disponibles ? 'active' : ''; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page_disponibles < $total_pages_disponibles): ?>
                <a href="?page_disponibles=<?= $page_disponibles + 1; ?>&page_inscritas=<?= $page_inscritas; ?>" class="btn-general">Siguiente</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="mensaje-info">No hay clases disponibles para tus especialidades.</p>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>
<script src="../../assets/js/clases.js"></script>
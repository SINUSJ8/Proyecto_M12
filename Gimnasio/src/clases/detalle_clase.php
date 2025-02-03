<?php
require_once '../clases/class_functions.php';
require_once('../admin/admin_functions.php');
verificarAdmin();

$conn = obtenerConexion();

// Verificar que se recibió el id_clase
if (!isset($_GET['id_clase']) || !is_numeric($_GET['id_clase'])) {
    die("ID de clase no válido.");
}

$id_clase = intval($_GET['id_clase']);

// Obtener los detalles de la clase
$clase = obtenerDetallesClase($conn, $id_clase);

// Obtener los miembros inscritos
$miembros = obtenerMiembrosInscritos($conn, $id_clase);

// Recuperar la URL de referencia o asignar una por defecto
$referer = $_SERVER['HTTP_REFERER'] ?? 'buscar_clase.php';

$title = "Detalle de la Clase";
include '../admin/admin_header.php';
?>

<body>
    <main>
        <h2>Detalle de la Clase: <?= htmlspecialchars($clase['nombre']); ?></h2>
        <p><strong>Especialidad:</strong> <?= htmlspecialchars($clase['especialidad']); ?></p>
        <p><strong>Monitor:</strong> <?= htmlspecialchars($clase['monitor']); ?></p>
        <p><strong>Fecha:</strong> <?= date('d-m-Y', strtotime($clase['fecha'])); ?></p>
        <p><strong>Horario:</strong> <?= htmlspecialchars($clase['horario']); ?></p>
        <p><strong>Duración:</strong> <?= htmlspecialchars($clase['duracion']); ?> min</p>
        <p><strong>Capacidad Máxima:</strong> <?= htmlspecialchars($clase['capacidad_maxima']); ?></p>
        <p><strong>Miembros Inscritos:</strong> <?= count($miembros); ?> / <?= htmlspecialchars($clase['capacidad_maxima']); ?></p>

        <h3>Miembros Apuntados</h3>
        <?php if (!empty($miembros)): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($miembros as $miembro): ?>
                        <tr>
                            <td><?= htmlspecialchars($miembro['nombre']); ?></td>
                            <td><?= htmlspecialchars($miembro['email']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay miembros inscritos en esta clase.</p>
        <?php endif; ?>

        <div class="button-container">
            <a href="<?= htmlspecialchars($referer); ?>" class="btn-general btn-secondary">Volver</a>
        </div>
    </main>
</body>
<?php
require_once('../admin/admin_functions.php');
verificarAdmin();
$conn = obtenerConexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idMembresia = intval($_POST['id_membresia']);
    $accion = $_POST['accion'];
    $resultado = false;

    // Capturar el término de búsqueda desde $_POST
    $busqueda = isset($_POST['busqueda']) ? $_POST['busqueda'] : '';

    if ($idMembresia <= 0) {
        header("Location: membresias.php?error=ID de membresía inválido&busqueda=" . urlencode($busqueda));
        exit();
    }

    if ($accion === 'activar') {
        $resultado = activarMembresia($conn, $idMembresia);
        if (!$resultado) {
            header("Location: membresias.php?error=No se puede activar una membresía con fecha de expiración pasada.&busqueda=" . urlencode($busqueda));
            exit();
        }
    } elseif ($accion === 'desactivar') {
        $resultado = desactivarMembresia($conn, $idMembresia);
    } elseif ($accion === 'eliminar') {
        $resultado = eliminarMiembroMembresia($conn, $idMembresia);
    } else {
        header("Location: membresias.php?error=Acción no válida&busqueda=" . urlencode($busqueda));
        exit();
    }

    if ($resultado) {
        header("Location: membresias.php?mensaje=Acción realizada con éxito&busqueda=" . urlencode($busqueda));
    } else {
        header("Location: membresias.php?error=Error al realizar la acción&busqueda=" . urlencode($busqueda));
    }
}

$conn->close();

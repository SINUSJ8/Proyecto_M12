<?php
require_once('../admin/admin_functions.php');
verificarAdmin();
$conn = obtenerConexion();

// Asegurar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Solicitud inválida."]);
    exit();
}

$idMembresia = isset($_POST['id_membresia']) ? intval($_POST['id_membresia']) : 0;
$accion = $_POST['accion'] ?? '';
$busqueda = $_POST['busqueda'] ?? '';

if ($idMembresia <= 0) {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "ID de membresía inválido."]);
    exit();
}

$resultado = false;
$mensaje = "";

// Ejecutar la acción correspondiente
if ($accion === 'activar') {
    $resultado = activarMembresia($conn, $idMembresia);
    $mensaje = $resultado ? "Membresía activada correctamente." : "No se puede activar una membresía con fecha de expiración pasada.";
} elseif ($accion === 'desactivar') {
    $resultado = desactivarMembresia($conn, $idMembresia);
    $mensaje = $resultado ? "Membresía desactivada correctamente." : "Error al desactivar la membresía.";
} elseif ($accion === 'eliminar') {
    $resultado = eliminarMiembroMembresia($conn, $idMembresia);
    $mensaje = $resultado ? "Membresía eliminada correctamente." : "Error al eliminar la membresía.";
} else {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Acción no válida."]);
    exit();
}

// Asegurar que solo se devuelve JSON sin espacios en blanco
header("Content-Type: application/json");
echo json_encode(["status" => $resultado ? "success" : "error", "message" => $mensaje]);

$conn->close();
exit(); // 🔥 IMPORTANTE: Evitar salida de datos adicionales

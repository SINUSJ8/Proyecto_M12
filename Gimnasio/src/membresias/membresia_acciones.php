<?php
require_once('../admin/admin_functions.php');
require_once('../clases/class_functions.php'); // Asegurar que se importe la función enviarNotificacion
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

// Obtener el usuario y el tipo de membresía afectada
$stmt = $conn->prepare("
    SELECT mb.id_usuario, m.tipo 
    FROM miembro_membresia mm
    INNER JOIN miembro mb ON mm.id_miembro = mb.id_miembro
    INNER JOIN membresia m ON mm.id_membresia = m.id_membresia
    WHERE mm.id = ?
");
$stmt->bind_param("i", $idMembresia);
$stmt->execute();
$stmt->bind_result($idUsuario, $tipoMembresia);
$stmt->fetch();
$stmt->close();

if (!$idUsuario) {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "No se encontró el usuario afectado."]);
    exit();
}

// Ejecutar la acción correspondiente
if ($accion === 'activar') {
    $resultado = activarMembresia($conn, $idMembresia);
    if ($resultado) {
        $mensaje = "Membresía activada correctamente.";
        enviarNotificacion($conn, $idUsuario, "Tu membresía '$tipoMembresia' ha sido activada.");
    } else {
        $mensaje = "No se puede activar una membresía con fecha de expiración pasada.";
    }
} elseif ($accion === 'desactivar') {
    $resultado = desactivarMembresia($conn, $idMembresia);
    if ($resultado) {
        $mensaje = "Membresía desactivada correctamente.";
        enviarNotificacion($conn, $idUsuario, "Tu membresía '$tipoMembresia' ha sido desactivada.");
    } else {
        $mensaje = "Error al desactivar la membresía.";
    }
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
echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $mensaje
]);

$conn->close();
exit();

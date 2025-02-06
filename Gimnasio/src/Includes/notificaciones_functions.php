<?php
require_once('../includes/general.php');
// Enviar una nueva notificación
function enviarNotificacion($conn, $id_usuario, $mensaje)
{
    $stmt = $conn->prepare("INSERT INTO notificacion (id_usuario, mensaje) VALUES (?, ?)");
    $stmt->bind_param("is", $id_usuario, $mensaje);
    $stmt->execute();
    $stmt->close();
}
function obtenerNotificaciones($conn, $id_usuario, $limit = 5, $soloNoLeidas = true)
{
    $sql = "SELECT n.* FROM notificacion n
            LEFT JOIN notificacion_oculta no ON n.id_notificacion = no.id_notificacion AND no.id_usuario = ?
            WHERE n.id_usuario = ? AND no.id_notificacion IS NULL"; // ← Corregido aquí

    if ($soloNoLeidas) {
        $sql .= " AND n.leida = 0";
    }
    $sql .= " ORDER BY n.fecha DESC LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $id_usuario, $id_usuario, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $notificaciones;
}


function obtenerNotificacionesPorUsuario($conn, $id_usuario)
{
    $sql = "SELECT n.mensaje, n.fecha, n.leida 
            FROM notificacion n
            LEFT JOIN notificacion_oculta no ON n.id_notificacion = no.id_notificacion AND no.id_usuario = ?
            WHERE n.id_usuario = ? AND no.id_notificacion IS NULL"; // ← Cambiado aquí

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $notificaciones = [];
    if ($result) {
        $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();

    return $notificaciones;
}


// Marcar todas las notificaciones como leídas
function marcarNotificacionesComoLeidas($conn, $id_usuario)
{
    $sql = "UPDATE notificacion n
            LEFT JOIN notificacion_oculta no ON n.id_notificacion = no.id_notificacion AND no.id_usuario = ?
            SET n.leida = 1 
            WHERE n.id_usuario = ? AND no.id_oculta IS NULL AND n.leida = 0";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_usuario);
    $stmt->execute();
    $stmt->close();
}

// Ocultar una notificación para un usuario
function ocultarNotificacion($conn, $id_notificacion, $id_usuario)
{
    $stmt = $conn->prepare("INSERT INTO notificacion_oculta (id_usuario, id_notificacion) VALUES (?, ?)"); // ← Quitamos IGNORE
    $stmt->bind_param("ii", $id_usuario, $id_notificacion);
    if ($stmt->execute()) {
        redirigirAOrigen("Notificación ocultada");
    } else {
        redirigirAOrigen("Error al ocultar la notificación", true);
    }
}


// Restaurar una notificación previamente oculta
function restaurarNotificacion($conn, $id_notificacion, $id_usuario)
{
    $stmt = $conn->prepare("DELETE FROM notificacion_oculta WHERE id_usuario = ? AND id_notificacion = ?");
    $stmt->bind_param("ii", $id_usuario, $id_notificacion);
    if ($stmt->execute()) {
        redirigirAOrigen("Notificación restaurada");
    } else {
        redirigirAOrigen("Error al restaurar la notificación", true);
    }
}

// Función para redirigir manteniendo el origen
function redirigirAOrigen($mensaje, $error = false)
{
    $redirect_page = "mis_notificaciones.php"; // Página por defecto

    if (!empty($_POST['redirect']) && in_array($_POST['redirect'], ['notificaciones.php', 'notificaciones_enviadas.php', 'notificaciones_monitor.php', 'mis_notificaciones.php', 'user_notificaciones.php'])) {
        $redirect_page = $_POST['redirect'];
    } elseif (isset($_SERVER['HTTP_REFERER'])) {
        $referer = basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));
        if (in_array($referer, ['notificaciones.php', 'notificaciones_enviadas.php', 'notificaciones_monitor.php', 'mis_notificaciones.php', 'user_notificaciones.php'])) {
            $redirect_page = $referer;
        }
    }

    $param = $error ? "error" : "success";
    header("Location: $redirect_page?$param=" . urlencode($mensaje));
    exit();
}
function obtenerNotificacionesEnviadas($conn, $buscar = '', $fecha_inicio = '', $fecha_fin = '', $limit = 15, $offset = 0)
{
    // Base de la consulta
    $sql = "SELECT n.id_notificacion, u.nombre, u.email, n.mensaje, 
               DATE_FORMAT(n.fecha, '%d/%m/%Y') AS fecha, n.leida,
               (SELECT COUNT(*) FROM notificacion_oculta no WHERE no.id_notificacion = n.id_notificacion) AS esta_oculta
        FROM notificacion n
        INNER JOIN usuario u ON n.id_usuario = u.id_usuario
        WHERE 1=1";


    $params = [];
    $types = "";

    // Filtro por nombre o correo
    if (!empty($buscar)) {
        $sql .= " AND (u.nombre LIKE ? OR u.email LIKE ?)";
        $buscar_param = "%$buscar%";
        $params[] = $buscar_param;
        $params[] = $buscar_param;
        $types .= "ss";
    }

    // Filtro por rango de fechas
    if (!empty($fecha_inicio)) {
        $sql .= " AND n.fecha >= ?";
        $params[] = $fecha_inicio;
        $types .= "s";
    }

    if (!empty($fecha_fin)) {
        $sql .= " AND n.fecha <= ?";
        $params[] = $fecha_fin;
        $types .= "s";
    }

    // Orden y paginación
    $sql .= " ORDER BY n.fecha DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}
// Ocultar todas las notificaciones de un usuario
function ocultarTodasNotificaciones($conn, $id_usuario)
{
    $stmt = $conn->prepare("
        INSERT IGNORE INTO notificacion_oculta (id_usuario, id_notificacion)
        SELECT ?, id_notificacion FROM notificacion
        WHERE id_notificacion NOT IN (
            SELECT id_notificacion FROM notificacion_oculta WHERE id_usuario = ?
        )
    ");
    $stmt->bind_param("ii", $id_usuario, $id_usuario);
    $stmt->execute();
    $stmt->close();
    redirigirAOrigen("Todas las notificaciones han sido ocultadas.");
}

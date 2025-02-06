<?php
require_once('../includes/general.php');
// Función para obtener las notificaciones
function obtenerNotificaciones($conn, $id_usuario, $limit = 5, $soloNoLeidas = true)
{
    $sql = "SELECT * FROM notificacion WHERE id_usuario = ?";
    if ($soloNoLeidas) {
        $sql .= " AND leida = 0";
    }
    $sql .= " ORDER BY fecha DESC LIMIT ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notificaciones;
}
function enviarNotificacion($conn, $id_usuario, $mensaje)
{
    $stmt = $conn->prepare("INSERT INTO notificacion (id_usuario, mensaje) VALUES (?, ?)");
    $stmt->bind_param("is", $id_usuario, $mensaje);
    $stmt->execute();
    $stmt->close();
}
function obtenerNotificacionesPorUsuario($conn, $id_usuario)
{
    $sql = "SELECT mensaje, fecha, leida 
            FROM notificacion 
            WHERE id_usuario = ? 
            ORDER BY fecha DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $notificaciones = [];
    if ($result) {
        $notificaciones = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();

    return $notificaciones;
}
function marcarNotificacionesComoLeidas($conn, $id_usuario)
{
    $sql = "UPDATE notificacion SET leida = 1 WHERE id_usuario = ? AND leida = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->close();
}

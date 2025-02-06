<?php
require_once('../includes/general.php');
require_once('../includes/notificaciones_functions.php');
$conn = obtenerConexion();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_notificacion"])) {
    $id_notificacion = intval($_POST["id_notificacion"]);
    $id_usuario = $_SESSION['id_usuario'];

    if (isset($_POST['accion']) && $_POST['accion'] === 'ocultar') {
        ocultarNotificacion($conn, $id_notificacion, $id_usuario);
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'restaurar') {
        restaurarNotificacion($conn, $id_notificacion, $id_usuario);
    }
}

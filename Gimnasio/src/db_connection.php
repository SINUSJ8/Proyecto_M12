<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "actividad_02";

// Crear conexión a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar si hay errores de conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

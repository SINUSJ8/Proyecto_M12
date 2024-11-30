<?php
session_start();
session_unset();  // Limpiar todas las variables de sesión
session_destroy();  // Destruir la sesión

header("Location: ../index.php");  // Redirigir a la página principal en la raíz
exit();

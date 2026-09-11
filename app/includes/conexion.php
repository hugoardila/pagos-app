<?php
$conexion = new mysqli(
    getenv('PAGOS_APP_DB_HOST') ?: '',
    getenv('PAGOS_APP_DB_USER') ?: '',
    getenv('PAGOS_APP_DB_PASS') ?: '',
    getenv('PAGOS_APP_DB_NAME') ?: ''
);
if ($conexion->connect_error) {
    die("Error de conexi?n: " . $conexion->connect_error);
}
$conexion->set_charset('utf8mb4');
?>

<?php
$conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
$nombre = isset($_GET['nombre']) ? $conexion->real_escape_string($_GET['nombre']) : '';
$sql = "SELECT id, nombre FROM empleados WHERE activo = 1 AND nombre LIKE '{$nombre}%' LIMIT 10";
$resultado = $conexion->query($sql);
$empleados = [];
while ($fila = $resultado->fetch_assoc()) {
    $empleados[] = $fila;
}
header('Content-Type: application/json');
echo json_encode($empleados);
$conexion->close();
?>
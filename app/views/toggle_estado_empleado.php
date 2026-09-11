<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: empleados.php");
    exit();
}

$id = intval($_GET['id']);

$conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$result = $conexion->query("SELECT activo FROM empleados WHERE id = $id LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $nuevo_estado = $row['activo'] ? 0 : 1;
    $conexion->query("UPDATE empleados SET activo = $nuevo_estado WHERE id = $id");
    $estado = $nuevo_estado ? 'activado' : 'desactivado';
    header("Location: empleados.php?estado=$estado");
    exit();
}

header("Location: empleados.php");
exit();
?>


<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_POST['estado_actual'])) {
    $conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $id = intval($_POST['id']);
    $estado_actual = $_POST['estado_actual'];
    $nuevo_estado = ($estado_actual === 'activo') ? 'inactivo' : 'activo';

    $stmt = $conexion->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $id);

    if ($stmt->execute()) {
        header("Location: usuarios.php");
        exit();
    } else {
        die("Error al cambiar estado: " . $stmt->error);
    }
}
?>

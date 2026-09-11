
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $id = intval($_POST['id']);

    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: usuarios.php");
        exit();
    } else {
        die("Error al eliminar usuario: " . $stmt->error);
    }
}
?>

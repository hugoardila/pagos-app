
<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $id = intval($_POST['id']);
    $usuario_sede = intval($_SESSION['usuario_sede']);

    $verificar = $conexion->prepare("SELECT id_sede FROM usuarios WHERE id = ?");
    $verificar->bind_param("i", $id);
    $verificar->execute();
    $verificar->bind_result($sede_registro);
    $verificar->fetch();
    $verificar->close();

    if ($sede_registro !== $usuario_sede) {
        die("Acceso denegado. No puedes eliminar usuarios de otra sede.");
    }

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

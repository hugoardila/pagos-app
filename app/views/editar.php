
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $id_rol = intval($_POST['id_rol']);
    $id_sede = intval($_POST['id_sede']);

    if (empty($nombre) || empty($email) || $id_rol <= 0 || $id_sede <= 0) {
        die("Faltan datos obligatorios.");
    }

    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("
            UPDATE usuarios 
            SET nombre = ?, email = ?, password = ?, id_rol = ?, id_sede = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("sssiii", $nombre, $email, $password_hash, $id_rol, $id_sede, $id);
    } else {
        $stmt = $conexion->prepare("
            UPDATE usuarios 
            SET nombre = ?, email = ?, id_rol = ?, id_sede = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssiii", $nombre, $email, $id_rol, $id_sede, $id);
    }

    if ($stmt->execute()) {
        header("Location: usuarios.php");
        exit();
    } else {
        die("Error al actualizar usuario: " . $stmt->error);
    }
}
?>

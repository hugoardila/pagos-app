
<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT u.*, r.nombre AS rol 
              FROM usuarios u 
              JOIN roles r ON u.id_rol = r.id 
              WHERE u.email = ?";

    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows == 1) {
        $usuario = $resultado->fetch_assoc();

        echo "<pre>";
        echo "Resultado de la consulta:<br>";
        print_r($usuario);
        echo "</pre>";
        exit;

    } else {
        echo "Usuario no encontrado.";
    }
}
?>

<!-- Formulario HTML para probar login -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Debug</title>
</head>
<body>
    <h2>Login Debug</h2>
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" required><br>
        <label>Contraseña:</label>
        <input type="password" name="password" required><br>
        <button type="submit">Probar Login</button>
    </form>
</body>
</html>

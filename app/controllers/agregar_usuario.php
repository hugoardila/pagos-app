
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Obtener datos del formulario
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $id_rol = intval($_POST['id_rol']);
    $id_sede = intval($_POST['id_sede']);

    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($password) || $id_rol <= 0 || $id_sede <= 0) {
        die("Faltan datos obligatorios.");
    }

    // Verificar si ya existe un usuario con el mismo email
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $conexion->close();
        die("Ya existe un usuario con este correo.");
    }
    $stmt->close();

    // Encriptar contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $stmt = $conexion->prepare("
        INSERT INTO usuarios (nombre, email, password, id_rol, id_sede, estado)
        VALUES (?, ?, ?, ?, ?, 'activo')
    ");
    $stmt->bind_param("sssii", $nombre, $email, $password_hash, $id_rol, $id_sede);

    if ($stmt->execute()) {
        header("Location: ../views/usuarios.php");
        exit();
    } else {
        die("Error al guardar usuario: " . $stmt->error);
    }
}
?>

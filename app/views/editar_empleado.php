<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if (!isset($_GET['id'])) {
    echo "ID de empleado no proporcionado.";
    exit();
}

$id = intval($_GET['id']);

// Obtener datos del empleado
$resultado = $conexion->query("SELECT * FROM empleados WHERE id = $id");
if ($resultado->num_rows === 0) {
    echo "Empleado no encontrado.";
    exit();
}
$empleado = $resultado->fetch_assoc();

// Obtener todas las sedes
$sedes = $conexion->query("SELECT * FROM sedes");
$roles = $conexion->query("SELECT * FROM rol_empleado");


// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $documento = $conexion->real_escape_string($_POST['documento']);
    $telefono = $conexion->real_escape_string($_POST['telefono']);
    $id_sede = intval($_POST['id_sede']);
    $rol_id = intval($_POST['rol_id']);

    $conexion->query("UPDATE empleados SET nombre='$nombre', documento='$documento', telefono='$telefono', id_sede=$id_sede,  rol_id=$rol_id WHERE id=$id");
    header("Location: empleados.php?estado=editado");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 15px 0;
            margin-bottom: 30px;
        }
        
        .main-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            animation: fadeInDown 0.6s ease-out;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-header h2 {
            color: #2d3748;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .page-header p {
            color: #718096;
            margin: 0;
        }
        
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group-custom {
            margin-bottom: 25px;
        }
        
        .form-label-custom {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-label-custom i {
            margin-right: 5px;
            color: #667eea;
        }
        
        .form-control-custom, .form-select-custom {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control-custom:focus, .form-select-custom:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .buttons-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-save {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 14px 35px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-cancel {
            flex: 1;
            background: #6c757d;
            border: none;
            color: white;
            padding: 14px 35px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }
        
        @media (max-width: 768px) {
            .form-card {
                padding: 25px;
            }
            
            .buttons-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-custom">
        <div class="container-fluid px-4">
            <a href="../index.php" style="text-decoration: none; color: white; font-size: 24px; font-weight: 700;">
                <i class="fas fa-money-check-alt"></i> Pagos App
            </a>
            <span style="color: white;">
                <i class="fas fa-user-circle"></i> <?php echo $_SESSION['usuario_nombre']; ?>
            </span>
        </div>
    </nav>

    <div class="main-container">
        <!-- Header -->
        <div class="page-header">
            <h2><i class="fas fa-user-edit"></i> Editar Empleado</h2>
            <p>Actualice la información del empleado</p>
        </div>

        <!-- Formulario -->
        <div class="form-card">
            <form method="POST">
                <div class="form-group-custom">
                    <label for="nombre" class="form-label-custom">
                        <i class="fas fa-user"></i> Nombre Completo
                    </label>
                    <input type="text" 
                           class="form-control-custom" 
                           name="nombre" 
                           id="nombre" 
                           value="<?= htmlspecialchars($empleado['nombre']) ?>" 
                           required>
                </div>

                <div class="form-group-custom">
                    <label for="documento" class="form-label-custom">
                        <i class="fas fa-id-card"></i> Documento de Identidad
                    </label>
                    <input type="text" 
                           class="form-control-custom" 
                           name="documento" 
                           id="documento" 
                           value="<?= htmlspecialchars($empleado['documento']) ?>" 
                           required>
                </div>

                <div class="form-group-custom">
                    <label for="telefono" class="form-label-custom">
                        <i class="fas fa-phone"></i> Teléfono
                    </label>
                    <input type="text" 
                           class="form-control-custom" 
                           name="telefono" 
                           id="telefono" 
                           value="<?= htmlspecialchars($empleado['telefono']) ?>">
                </div>

                <div class="form-group-custom">
                    <label for="id_sede" class="form-label-custom">
                        <i class="fas fa-building"></i> Sede
                    </label>
                    <select class="form-select-custom" name="id_sede" id="id_sede" required>
                        <?php while ($sede = $sedes->fetch_assoc()): ?>
                            <option value="<?= $sede['id'] ?>" <?= $sede['id'] == $empleado['id_sede'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sede['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label for="rol_id" class="form-label-custom">
                        <i class="fas fa-user-tag"></i> Rol
                    </label>
                    <select class="form-select-custom" name="rol_id" id="rol_id" required>
                        <option value="">Seleccione un rol</option>
                        <?php while ($rol = $roles->fetch_assoc()): ?>
                            <option value="<?= $rol['id'] ?>" <?= $rol['id'] == $empleado['rol_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="buttons-group">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="empleados.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

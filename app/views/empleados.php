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

$mensaje = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todos';
$busqueda = isset($_GET['busqueda']) ? $conexion->real_escape_string($_GET['busqueda']) : '';

$where = [];
if ($filtro === 'activos') {
    $where[] = 'empleados.activo = 1';
} elseif ($filtro === 'inactivos') {
    $where[] = 'empleados.activo = 0';
}
if (!empty($busqueda)) {
    $where[] = "empleados.nombre LIKE '%$busqueda%'";
}
$condicion = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT empleados.id, empleados.nombre, empleados.documento, empleados.telefono, empleados.activo, sedes.nombre AS sede 
          FROM empleados 
          INNER JOIN sedes ON empleados.id_sede = sedes.id 
          $condicion
          ORDER BY empleados.activo DESC, empleados.nombre ASC";
$resultado = $conexion->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados</title>
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
            max-width: 1400px;
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
            margin: 0;
        }
        
        .actions-bar {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .btn-custom-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-custom-success {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 214, 160, 0.4);
            color: white;
        }
        
        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .form-select, .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 8px 15px;
            transition: all 0.3s ease;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .table {
            margin: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .table thead th {
            padding: 15px;
            font-weight: 600;
            border: none;
            vertical-align: middle;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background: #f8f9ff;
        }
        
        .badge-custom {
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .badge-active {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            color: white;
        }
        
        .badge-inactive {
            background: #6c757d;
            color: white;
        }
        
        .btn-action {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            margin: 2px;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        
        .btn-edit:hover {
            background: #ffcd39;
            transform: translateY(-2px);
        }
        
        .btn-toggle {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
        }
        
        .btn-toggle:hover {
            background: #667eea;
            color: white;
        }
        
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .alert-success-custom {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-warning-custom {
            background: #fff3cd;
            color: #856404;
        }
        
        @media (max-width: 768px) {
            .actions-bar {
                flex-direction: column;
            }
            
            .search-form {
                flex-direction: column;
                width: 100%;
            }
            
            .search-form .form-select,
            .search-form .form-control,
            .search-form .btn {
                width: 100%;
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
            <h2><i class="fas fa-users"></i> Gestión de Empleados</h2>
        </div>

        <!-- Barra de acciones -->
        <div class="actions-bar">
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="../index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a href="nuevo_empleado.php" class="btn btn-custom-success">
                    <i class="fas fa-user-plus"></i> Agregar Empleado
                </a>
            </div>
            
            <form method="GET" class="search-form">
                <select name="filtro" class="form-select" style="min-width: 150px;" onchange="this.form.submit()">
                    <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>
                        <i class="fas fa-list"></i> Todos
                    </option>
                    <option value="activos" <?= $filtro === 'activos' ? 'selected' : '' ?>>Activos</option>
                    <option value="inactivos" <?= $filtro === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                </select>
                <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" 
                       class="form-control" placeholder="Buscar por nombre" style="min-width: 200px;">
                <button type="submit" class="btn btn-custom-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </form>
        </div>

        <!-- Alertas -->
        <?php if ($mensaje === 'activado'): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle fa-lg"></i>
                <span>Empleado activado correctamente.</span>
            </div>
        <?php elseif ($mensaje === 'desactivado'): ?>
            <div class="alert-custom alert-warning-custom">
                <i class="fas fa-exclamation-triangle fa-lg"></i>
                <span>Empleado desactivado correctamente.</span>
            </div>
        <?php endif; ?>

        <!-- Tabla -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-user"></i> Nombre</th>
                        <th><i class="fas fa-id-card"></i> Documento</th>
                        <th><i class="fas fa-phone"></i> Teléfono</th>
                        <th><i class="fas fa-building"></i> Sede</th>
                        <th><i class="fas fa-toggle-on"></i> Estado</th>
                        <th><i class="fas fa-cogs"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $fila['id'] ?></strong></td>
                            <td><?= htmlspecialchars($fila['nombre']) ?></td>
                            <td><?= htmlspecialchars($fila['documento']) ?></td>
                            <td><?= htmlspecialchars($fila['telefono']) ?></td>
                            <td><?= htmlspecialchars($fila['sede']) ?></td>
                            <td>
                                <?php if ($fila['activo']): ?>
                                    <span class="badge-custom badge-active">
                                        <i class="fas fa-check"></i> Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge-custom badge-inactive">
                                        <i class="fas fa-times"></i> Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="editar_empleado.php?id=<?= $fila['id'] ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <?php if ($fila['activo']): ?>
                                    <a href="toggle_estado_empleado.php?id=<?= $fila['id'] ?>&estado=desactivado" 
                                       class="btn-action btn-toggle">
                                        <i class="fas fa-toggle-off"></i> Desactivar
                                    </a>
                                <?php else: ?>
                                    <a href="toggle_estado_empleado.php?id=<?= $fila['id'] ?>&estado=activado" 
                                       class="btn-action btn-toggle">
                                        <i class="fas fa-toggle-on"></i> Activar
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

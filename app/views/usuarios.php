
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_rol']) || trim(strtolower($_SESSION['usuario_rol'])) !== 'admin') {
    die("<p>⛔ Acceso denegado. Solo administradores.</p>");
}

$conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
if ($conexion->connect_error) {
    die("<p>❌ Error de conexión: " . $conexion->connect_error . "</p>");
}

$usuarios = $conexion->query("
    SELECT u.*, r.nombre AS nombre_rol 
    FROM usuarios u 
    JOIN roles r ON u.id_rol = r.id
");

$modales = "";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
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
        
        .btn-add-user {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 25px;
        }
        
        .btn-add-user:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 214, 160, 0.4);
            color: white;
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
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            margin: 2px;
        }
        
        .btn-toggle-status {
            background: #667eea;
            color: white;
        }
        
        .btn-toggle-status:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-edit-action {
            background: #ffc107;
            color: #000;
        }
        
        .btn-edit-action:hover {
            background: #ffcd39;
            transform: translateY(-2px);
        }
        
        .btn-delete-action {
            background: #ef476f;
            color: white;
        }
        
        .btn-delete-action:hover {
            background: #d62828;
            transform: translateY(-2px);
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-header.bg-danger {
            background: linear-gradient(135deg, #ef476f 0%, #d62828 100%) !important;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger-custom {
            background: linear-gradient(135deg, #ef476f 0%, #d62828 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 71, 111, 0.4);
        }
        
        .btn-secondary-custom {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary-custom:hover {
            background: #5a6268;
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
            <h2><i class="fas fa-users-cog"></i> Administrar Usuarios</h2>
            <a href="../index.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <!-- Botón agregar -->
        <button class="btn btn-add-user" data-bs-toggle="modal" data-bs-target="#modalAgregar">
            <i class="fas fa-user-plus"></i> Agregar Usuario
        </button>

        <!-- Tabla -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Nombre</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-user-tag"></i> Rol</th>
                        <th><i class="fas fa-toggle-on"></i> Estado</th>
                        <th><i class="fas fa-cogs"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <form method="POST" action="estado_usuario.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $usuario['id']; ?>">
                                    <input type="hidden" name="estado_actual" value="<?= $usuario['estado']; ?>">
                                    <button type="submit" 
                                            class="btn-action btn-toggle-status" 
                                            title="Activar/Inactivar">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </form>
                                <strong><?= htmlspecialchars($usuario['nombre']); ?></strong>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <span class="badge-custom badge-active">
                                <?= htmlspecialchars($usuario['nombre_rol']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($usuario['estado'] === 'activo'): ?>
                                <span class="badge-custom badge-active">
                                    <i class="fas fa-check-circle"></i> Activo
                                </span>
                            <?php else: ?>
                                <span class="badge-custom badge-inactive">
                                    <i class="fas fa-times-circle"></i> Inactivo
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-action btn-edit-action" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalEditar<?= $usuario['id']; ?>">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn-action btn-delete-action" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalEliminar<?= $usuario['id']; ?>">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </td>
                    </tr>
            <?php
            ob_start(); ?>
            <!-- Modal Editar -->
            <div class="modal fade" id="modalEditar<?= $usuario['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="editar.php" class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar Usuario</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $usuario['id']; ?>">
                            <div class="mb-3">
                                <label>Nombre</label>
                                <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($usuario['nombre']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($usuario['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Contraseña (dejar en blanco para no cambiar)</label>
                                <input type="password" class="form-control" name="password">
                            </div>
                            <div class="mb-3">
                                <label>Rol</label>
                                <select name="id_rol" class="form-select" required>
                                    <option value="1" <?= $usuario['id_rol'] == 1 ? 'selected' : '' ?>>Admin</option>
                                    <option value="2" <?= $usuario['id_rol'] == 2 ? 'selected' : '' ?>>Usuario</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Sede</label>
                                <input type="number" name="id_sede" class="form-control" value="<?= $usuario['id_sede']; ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                            <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Modal Eliminar -->
            <div class="modal fade" id="modalEliminar<?= $usuario['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="eliminar.php" class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Eliminar Usuario</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ¿Estás seguro de que deseas eliminar a <strong><?= htmlspecialchars($usuario['nombre']); ?></strong>?
                            <input type="hidden" name="id" value="<?= $usuario['id']; ?>">
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-danger-custom">
                                <i class="fas fa-trash"></i> Sí, eliminar
                            </button>
                            <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php
            $modales .= ob_get_clean();
            ?>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- Imprimir todos los modales al final del body -->
<?= $modales ?>

<!-- Modal Agregar Usuario -->
<div class="modal fade" id="modalAgregar" tabindex="-1" aria-labelledby="modalAgregarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content needs-validation" method="POST" action="../controllers/agregar_usuario.php" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarLabel">Agregar Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Nombre</label>
                    <input type="text" class="form-control" name="nombre" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label>Contraseña</label>
                    <input type="password" class="form-control" name="password" required minlength="6">
                </div>
                <div class="mb-3">
                    <label>Rol</label>
                    <select name="id_rol" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <option value="1">Admin</option>
                        <option value="2">Usuario</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Sede</label>
                    <input type="number" name="id_sede" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary-custom">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

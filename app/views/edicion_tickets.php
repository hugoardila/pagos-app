<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include '../includes/conexion.php';

// Obtener sedes
$sedes = $conexion->query("SELECT * FROM sedes")->fetch_all(MYSQLI_ASSOC);

// Obtener filtros
$filtro_sede = $_GET['sede'] ?? '';
$filtro_nombre = $_GET['nombre'] ?? '';
$fecha_inicio = $_GET['desde'] ?? '';
$fecha_fin = $_GET['hasta'] ?? '';

// Armar WHERE dinámico
$where = "1=1";
if ($filtro_sede !== '') {
    $where .= " AND s.id = " . intval($filtro_sede);
}
if ($filtro_nombre !== '') {
    $nombre = $conexion->real_escape_string($filtro_nombre);
    $where .= " AND e.nombre LIKE '%$nombre%'";
}
if ($fecha_inicio !== '' && $fecha_fin !== '') {
    $fecha_inicio_sql = $conexion->real_escape_string($fecha_inicio . ' 00:00:00');
    $fecha_fin_sql = $conexion->real_escape_string($fecha_fin . ' 23:59:59');
    $where .= " AND t.fecha BETWEEN '$fecha_inicio_sql' AND '$fecha_fin_sql'";
}

// Obtener tickets
$query = "
  SELECT 
    t.*,
    e.nombre AS empleado,
    s.nombre AS sede,
    DATE_FORMAT(t.fecha, '%Y-%m-%d') AS fecha_orden,
    DATE_FORMAT(t.fecha, '%d/%m/%Y') AS fecha_mostrar,
    DATE_FORMAT(t.fecha, '%H:%i:%s') AS hora_mostrar
  FROM tickets t
  JOIN empleados e ON t.id_empleado = e.id
  JOIN sedes s ON e.id_sede = s.id
  WHERE $where
  ORDER BY fecha_orden DESC, hora_mostrar DESC, t.id DESC
";
$tickets = $conexion->query($query);
$empleados = $conexion->query("SELECT e.id, e.nombre, s.nombre AS sede FROM empleados e JOIN sedes s ON e.id_sede = s.id ORDER BY e.nombre ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edición de Tickets</title>
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
            max-width: 1600px;
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
        
        .filter-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .form-label-custom {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 14px;
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
        
        .btn-filter {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
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
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background: #f8f9ff;
        }
        
        .btn-edit {
            background: #ffc107;
            border: none;
            color: #000;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            background: #ffcd39;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
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
        
        .btn-success-custom {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 214, 160, 0.4);
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
        
        .alert-danger-custom {
            background: #f8d7da;
            color: #721c24;
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
            <h2><i class="fas fa-edit"></i> Editar Tickets Registrados</h2>
        </div>

        <!-- Alertas -->
        <?php if (isset($_GET['actualizado'])): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle fa-lg"></i>
                <span>¡Ticket actualizado correctamente!</span>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert-custom alert-danger-custom">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <span>Hubo un error al actualizar el ticket.</span>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filter-card">
            <h5 style="margin-bottom: 20px; color: #2d3748; font-weight: 600;">
                <i class="fas fa-filter"></i> Filtros de Búsqueda
            </h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label-custom">
                        <i class="fas fa-building"></i> Sede
                    </label>
                    <select name="sede" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $filtro_sede == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label-custom">
                        <i class="fas fa-user"></i> Nombre
                    </label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($filtro_nombre) ?>" class="form-control" placeholder="Buscar empleado...">
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom">
                        <i class="fas fa-calendar-alt"></i> Desde
                    </label>
                    <input type="date" name="desde" value="<?= $fecha_inicio ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label-custom">
                        <i class="fas fa-calendar-check"></i> Hasta
                    </label>
                    <input type="date" name="hasta" value="<?= $fecha_fin ?>" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-filter w-100">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de tickets -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user"></i> Empleado</th>
                            <th><i class="fas fa-building"></i> Sede</th>
                            <th><i class="fas fa-align-left"></i> Descripción</th>
                            <th><i class="fas fa-dollar-sign"></i> Monto</th>
                            <th><i class="fas fa-money-bill"></i> Moneda</th>
                            <th><i class="fas fa-tag"></i> Tipo</th>
                            <th><i class="fas fa-calendar"></i> Fecha</th>
                            <th><i class="fas fa-clock"></i> Hora</th>
                            <th><i class="fas fa-cogs"></i> Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $tickets->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $t['id'] ?></strong></td>
                                <td><?= htmlspecialchars($t['empleado']) ?></td>
                                <td><?= htmlspecialchars($t['sede']) ?></td>
                                <td><?= htmlspecialchars($t['descripcion']) ?></td>
                                <td><strong>$<?= number_format($t['monto'], 0, ',', '.') ?></strong></td>
                                <td><?= $t['moneda'] ?></td>
                                <td><?= ucfirst($t['tipo']) ?></td>
                                <td><?= htmlspecialchars($t['fecha_mostrar']) ?></td>
                                <td><?= htmlspecialchars($t['hora_mostrar']) ?></td>
                                <td>
                                    <button class="btn-edit" onclick='editarTicket(<?= json_encode($t) ?>)'>
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Botón volver -->
        <a href="registro_tickets.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver al Registro
        </a>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="controllers/actualizar_ticket.php" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Editar Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label-custom">
                            <i class="fas fa-user"></i> Empleado
                        </label>
                        <select name="id_empleado" id="edit_empleado" class="form-select" required>
                            <option value="">Seleccionar empleado</option>
                            <?php foreach ($empleados as $emp): ?>
                                <option value="<?= $emp['id'] ?>">
                                    <?= htmlspecialchars($emp['nombre']) . " - " . htmlspecialchars($emp['sede']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">
                            <i class="fas fa-align-left"></i> Descripción
                        </label>
                        <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">
                            <i class="fas fa-dollar-sign"></i> Monto
                        </label>
                        <input type="number" step="0.01" name="monto" id="edit_monto" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">
                            <i class="fas fa-money-bill"></i> Moneda
                        </label>
                        <select name="moneda" id="edit_moneda" class="form-select" required>
                            <option value="COP">COP</option>
                            <option value="USD">USD</option>
                            <option value="MXN">MXN</option>
                            <option value="QZ">QZ</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">
                            <i class="fas fa-tag"></i> Tipo
                        </label>
                        <select name="tipo" id="edit_tipo" class="form-select" required>
                            <option value="enganche">Enganche</option>
                            <option value="retaque">Retaque</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-success-custom">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editarTicket(ticket) {
            document.getElementById('edit_id').value = ticket.id;
            document.getElementById('edit_empleado').value = ticket.id_empleado;
            document.getElementById('edit_descripcion').value = ticket.descripcion;
            document.getElementById('edit_monto').value = ticket.monto;
            document.getElementById('edit_moneda').value = ticket.moneda;
            document.getElementById('edit_tipo').value = ticket.tipo;
            const modal = new bootstrap.Modal(document.getElementById('modalEditar'));
            modal.show();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
include('../includes/conexion.php');

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$sede = $_GET['sede'] ?? '';
$empleado = $_GET['empleado'] ?? '';
$tipo_ticket = $_GET['tipo_ticket'] ?? '';

$where = [];
if ($fecha_inicio && $fecha_fin) {
    $where[] = "tickets.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if ($sede !== '') {
    $where[] = "sedes.nombre = '$sede'";
}
if ($empleado !== '') {
    $where[] = "empleados.nombre = '$empleado'";
}
if ($tipo_ticket !== '') {
    $where[] = "tickets.tipo = '$tipo_ticket'";
}

$condiciones = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "SELECT tickets.fecha, empleados.nombre AS nombre_empleado, tickets.tipo, tickets.moneda, tickets.monto, sedes.nombre AS sede
          FROM tickets
          JOIN empleados ON tickets.id_empleado = empleados.id
          JOIN sedes ON empleados.id_sede = sedes.id
          $condiciones
          ORDER BY tickets.fecha DESC";
$resultado = $conexion->query($query);

// Datos para filtros
$sedes = $conexion->query("SELECT DISTINCT sedes.nombre FROM sedes INNER JOIN empleados ON empleados.id_sede = sedes.id");
$empleados = $conexion->query("SELECT DISTINCT nombre FROM empleados");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ingresos</title>
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
        }
        .btn-export {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
        }
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .table thead th {
            padding: 15px;
            font-weight: 600;
            border: none;
        }
        .table tbody td {
            padding: 12px 15px;
        }
        .table tbody tr:hover {
            background: #f8f9ff;
        }
        .btn-back {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
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
        <div class="page-header">
            <h2><i class="fas fa-chart-bar"></i> Reporte de Ingresos por Fechas</h2>
        </div>

        <div class="filter-card">
            <h5 style="margin-bottom: 20px; color: #2d3748; font-weight: 600;">
                <i class="fas fa-filter"></i> Filtros de Búsqueda
            </h5>
            <form class="row g-3" method="GET">
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-calendar-alt"></i> Fecha Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-calendar-check"></i> Fecha Fin</label>
                    <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="fas fa-building"></i> Sede</label>
                    <select name="sede" class="form-select">
                        <option value="">Todas</option>
                        <?php while ($row = $sedes->fetch_assoc()): ?>
                            <option value="<?= $row['nombre'] ?>" <?= $sede == $row['nombre'] ? 'selected' : '' ?>><?= $row['nombre'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="fas fa-user"></i> Empleado</label>
                    <select name="empleado" class="form-select">
                        <option value="">Todos</option>
                        <?php while ($row = $empleados->fetch_assoc()): ?>
                            <option value="<?= $row['nombre'] ?>" <?= $empleado == $row['nombre'] ? 'selected' : '' ?>><?= $row['nombre'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="fas fa-tag"></i> Tipo</label>
                    <select name="tipo_ticket" class="form-select">
                        <option value="">Todos</option>
                        <option value="enganche" <?= $tipo_ticket == 'enganche' ? 'selected' : '' ?>>Enganche</option>
                        <option value="retaque" <?= $tipo_ticket == 'retaque' ? 'selected' : '' ?>>Retaque</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="exportar_ingresos_excel.php?fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>&sede=<?= urlencode($sede) ?>&empleado=<?= urlencode($empleado) ?>&tipo_ticket=<?= urlencode($tipo_ticket) ?>" class="btn-export">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> Fecha</th>
                        <th><i class="fas fa-building"></i> Sede</th>
                        <th><i class="fas fa-user"></i> Empleado</th>
                        <th><i class="fas fa-tag"></i> Tipo</th>
                        <th><i class="fas fa-money-bill"></i> Moneda</th>
                        <th><i class="fas fa-dollar-sign"></i> Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['fecha'] ?></td>
                            <td><?= $row['sede'] ?></td>
                            <td><strong><?= $row['nombre_empleado'] ?></strong></td>
                            <td><?= ucfirst($row['tipo']) ?></td>
                            <td><?= $row['moneda'] ?></td>
                            <td style="color: #06d6a0; font-weight: 700;">$<?= number_format($row['monto'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center;">
            <a href="informes.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver a Informes
            </a>
        </div>
    </div>
</body>
</html>

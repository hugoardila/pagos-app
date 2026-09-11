<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/conexion.php';

// Filtros
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');
$id_sede = isset($_GET['id_sede']) ? intval($_GET['id_sede']) : '';

// Obtener sedes
$sedes = $conexion->query("SELECT * FROM sedes")->fetch_all(MYSQLI_ASSOC);

// Obtener tasas
$tasas = [];
$res_tasas = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
while ($row = $res_tasas->fetch_assoc()) {
    $tasas[$row['moneda']] = $row['tasa'];
}

// Consulta de ganancias mensuales
$where = "YEAR(ht.fecha) = $anio";
if ($id_sede !== '') {
    $where .= " AND e.id_sede = $id_sede";
}

$query = "
  SELECT 
    MONTH(ht.fecha) AS mes,
    YEAR(ht.fecha) AS anio,
    ht.tipo,
    ht.moneda,
    SUM(ht.monto) AS total,
    s.nombre AS sede
  FROM historico_tickets ht
  JOIN empleados e ON ht.id_empleado = e.id
  JOIN sedes s ON e.id_sede = s.id
  WHERE $where
  GROUP BY MONTH(ht.fecha), ht.tipo, ht.moneda, s.id
  ORDER BY mes DESC, sede
";

$resultado = $conexion->query($query);

// Calcular totales por mes
$totales_por_mes = [];
while ($row = $resultado->fetch_assoc()) {
    $mes = $row['mes'];
    $tipo = strtolower($row['tipo']);
    $moneda = $row['moneda'];
    $monto = $row['total'];
    $tasa = $tasas[$moneda] ?? 1;
    
    $monto_cop = $monto * $tasa;
    if ($tipo === 'enganche') {
        $monto_cop /= 2;
    } elseif ($tipo === 'retaque') {
        $monto_cop *= 0.30;
    }
    
    if (!isset($totales_por_mes[$mes])) {
        $totales_por_mes[$mes] = 0;
    }
    $totales_por_mes[$mes] += $monto_cop;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganancias Mensuales</title>
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
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .table thead {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            color: white;
        }
        .table thead th {
            padding: 15px;
            font-weight: 600;
            border: none;
        }
        .table tbody td {
            padding: 15px;
        }
        .table tbody tr:hover {
            background: #f0fff4;
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
            <h2><i class="fas fa-calendar-alt"></i> Ganancias Mensuales - Gastos</h2>
        </div>

        <div class="filter-card">
            <h5 style="margin-bottom: 20px; color: #2d3748; font-weight: 600;">
                <i class="fas fa-filter"></i> Filtros
            </h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" style="font-weight: 600;">
                        <i class="fas fa-calendar-alt"></i> Año
                    </label>
                    <select name="anio" class="form-select" onchange="this.form.submit()">
                        <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                            <option value="<?= $i ?>" <?= $anio == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 600;">
                        <i class="fas fa-building"></i> Sede
                    </label>
                    <select name="id_sede" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <?php foreach ($sedes as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $id_sede == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> Mes</th>
                        <th><i class="fas fa-calendar-alt"></i> Año</th>
                        <th><i class="fas fa-dollar-sign"></i> Total Ganancias (COP)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($totales_por_mes) > 0): ?>
                        <?php foreach ($totales_por_mes as $mes => $total): ?>
                            <tr>
                                <td><strong><?= date('F', mktime(0, 0, 0, $mes, 1)) ?></strong></td>
                                <td><?= $anio ?></td>
                                <td style="color: #06d6a0; font-weight: 700; font-size: 16px;">
                                    $<?= number_format($total, 0, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px; color: #718096;">
                                <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                                <p style="margin: 0;">No hay datos para mostrar</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center;">
            <a href="informes.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



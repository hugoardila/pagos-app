<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/conexion.php';

// Filtros
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Obtener tasas
$tasas = [];
$res_tasas = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
while ($row = $res_tasas->fetch_assoc()) {
    $tasas[$row['moneda']] = $row['tasa'];
}

// Consulta de ganancias por sede
$query = "
  SELECT 
    s.nombre AS sede,
    ht.tipo,
    ht.moneda,
    SUM(ht.monto) AS total
  FROM historico_tickets ht
  JOIN empleados e ON ht.id_empleado = e.id
  JOIN sedes s ON e.id_sede = s.id
  WHERE DATE(ht.fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'
  GROUP BY s.id, ht.tipo, ht.moneda
  ORDER BY s.nombre, ht.tipo
";

$resultado = $conexion->query($query);

// Calcular totales por sede
$totales_por_sede = [];
while ($row = $resultado->fetch_assoc()) {
    $sede = $row['sede'];
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
    
    if (!isset($totales_por_sede[$sede])) {
        $totales_por_sede[$sede] = 0;
    }
    $totales_por_sede[$sede] += $monto_cop;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganancias por Sede</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
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
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        .data-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .data-card h3 {
            color: #2d3748;
            font-weight: 600;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .table-custom {
            width: 100%;
        }
        .table-custom thead th {
            background: linear-gradient(135deg, #f8f9ff 0%, #f5f7fa 100%);
            padding: 12px;
            color: #2d3748;
            font-weight: 600;
            border-bottom: 2px solid #667eea;
        }
        .table-custom tbody td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
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
        }
        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
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
            <h2><i class="fas fa-building"></i> Ganancias por Sede</h2>
        </div>

        <div class="filter-card">
            <h5 style="margin-bottom: 20px; color: #2d3748; font-weight: 600;">
                <i class="fas fa-filter"></i> Filtrar por Fechas
            </h5>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 600;">
                        <i class="fas fa-calendar-alt"></i> Fecha Inicio
                    </label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 600;">
                        <i class="fas fa-calendar-check"></i> Fecha Fin
                    </label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn-filter w-100">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <div class="content-grid">
            <!-- Tabla -->
            <div class="data-card">
                <h3><i class="fas fa-table"></i> Ganancias por Sede</h3>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Sede</th>
                            <th class="text-end">Total (COP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($totales_por_sede) > 0): ?>
                            <?php foreach ($totales_por_sede as $sede => $total): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($sede) ?></strong></td>
                                    <td class="text-end" style="color: #06d6a0; font-weight: 700; font-size: 16px;">
                                        $<?= number_format($total, 0, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 40px; color: #718096;">
                                    No hay datos
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Gráfico -->
            <div class="data-card">
                <h3><i class="fas fa-chart-pie"></i> Distribución por Sede</h3>
                <canvas id="graficoSedes"></canvas>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="informes.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <script>
    const sedes = <?= json_encode(array_keys($totales_por_sede)) ?>;
    const totales = <?= json_encode(array_values($totales_por_sede)) ?>;
    
    const ctx = document.getElementById('graficoSedes').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: sedes,
            datasets: [{
                data: totales,
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(6, 214, 160, 0.8)',
                    'rgba(239, 71, 111, 0.8)',
                    'rgba(255, 209, 102, 0.8)',
                    'rgba(139, 92, 246, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



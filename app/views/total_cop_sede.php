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

$tasas = [];
$res_tasas = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
while ($row = $res_tasas->fetch_assoc()) {
    $tasas[strtoupper($row['moneda'])] = floatval($row['tasa']);
}

$totales = [];
$res_tickets = $conexion->query("SELECT tipo, moneda, SUM(monto) AS total FROM tickets GROUP BY tipo, moneda");
while ($row = $res_tickets->fetch_assoc()) {
    $tipo = strtoupper(trim((string)$row['tipo']));
    $moneda = strtoupper(trim((string)$row['moneda']));
    $clave = "$tipo ($moneda)";

    $total = floatval($row['total']);

    $totales[$clave] = $total;
}

$totales_cop = [];
$total_general = 0;
foreach ($totales as $clave => $valor) {
    if (preg_match('/(ENGANCHE|RETAQUE) \((\w+)\)/', $clave, $matches)) {
        $tipo = $matches[1];
        $moneda = $matches[2];
        $tasa = $tasas[$moneda] ?? 1.0;

        if ($tipo === 'ENGANCHE') {
            $convertido = ($valor * $tasa) / 2;
        } elseif ($tipo === 'RETAQUE') {
            $convertido = ($valor * $tasa) * 0.30;
        } else {
            $convertido = $valor * $tasa;
        }

        $totales_cop[$clave] = $convertido;
        $total_general += $convertido;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total COP por Sede</title>
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
        
        .data-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 100%;
            animation: fadeInUp 0.6s ease-out backwards;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .col:nth-child(1) .data-card { animation-delay: 0.1s; }
        .col:nth-child(2) .data-card { animation-delay: 0.2s; }
        .col:nth-child(3) .data-card { animation-delay: 0.3s; }
        .col:nth-child(4) .data-card { animation-delay: 0.4s; }
        
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body-custom {
            padding: 25px;
        }
        
        .table-custom {
            margin: 0;
        }
        
        .table-custom thead th {
            background: linear-gradient(135deg, #f8f9ff 0%, #f5f7fa 100%);
            color: #2d3748;
            font-weight: 600;
            padding: 12px 15px;
            border-bottom: 2px solid #667eea;
        }
        
        .table-custom tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .table-custom tbody tr {
            transition: background 0.2s ease;
        }
        
        .table-custom tbody tr:hover {
            background: #f8f9ff;
        }
        
        .table-success-custom {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            font-weight: 700;
            font-size: 16px;
        }
        
        .total-badge {
            display: inline-block;
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 18px;
        }
        
        canvas {
            max-height: 300px !important;
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
            <h2><i class="fas fa-building"></i> Total COP por Sede</h2>
            <a href="../index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <!-- Grid de datos -->
        <div class="row row-cols-1 row-cols-lg-2 g-4">
            <!-- Totales Originales -->
            <div class="col">
                <div class="data-card">
                    <div class="card-header-custom">
                        <i class="fas fa-list-ol"></i>
                        <span>Totales Originales</span>
                    </div>
                    <div class="card-body-custom">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($totales as $clave => $valor): ?>
                                    <tr>
                                        <td><strong><?= $clave ?></strong></td>
                                        <td class="text-end">$<?= number_format($valor, 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Totales Convertidos a COP -->
            <div class="col">
                <div class="data-card">
                    <div class="card-header-custom">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Totales Convertidos a COP</span>
                    </div>
                    <div class="card-body-custom">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th class="text-end">Total en COP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($totales_cop as $clave => $valor): ?>
                                    <tr>
                                        <td><strong><?= $clave ?></strong></td>
                                        <td class="text-end">$<?= number_format($valor, 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-success-custom">
                                    <td><strong>TOTAL GENERAL</strong></td>
                                    <td class="text-end">
                                        <span class="total-badge">
                                            $<?= number_format($total_general, 0, ',', '.') ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gráfico Totales Originales -->
            <div class="col">
                <div class="data-card">
                    <div class="card-header-custom">
                        <i class="fas fa-chart-bar"></i>
                        <span>Gráfico Totales Originales</span>
                    </div>
                    <div class="card-body-custom">
                        <canvas id="graficoOriginal"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico Totales en COP -->
            <div class="col">
                <div class="data-card">
                    <div class="card-header-custom">
                        <i class="fas fa-chart-pie"></i>
                        <span>Gráfico Totales en COP</span>
                    </div>
                    <div class="card-body-custom">
                        <canvas id="graficoCop"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const labelsOriginal = <?= json_encode(array_keys($totales)) ?>;
    const dataOriginal = <?= json_encode(array_values($totales)) ?>;
    const ctxOriginal = document.getElementById('graficoOriginal').getContext('2d');
    new Chart(ctxOriginal, {
        type: 'bar',
        data: {
            labels: labelsOriginal,
            datasets: [{
                label: 'Totales por tipo y moneda',
                data: dataOriginal,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    const labelsCop = <?= json_encode(array_keys($totales_cop)) ?>;
    const dataCop = <?= json_encode(array_values($totales_cop)) ?>;
    const ctxCop = document.getElementById('graficoCop').getContext('2d');
    new Chart(ctxCop, {
        type: 'bar',
        data: {
            labels: labelsCop,
            datasets: [{
                label: 'Totales convertidos a COP',
                data: dataCop,
                backgroundColor: 'rgba(6, 214, 160, 0.8)',
                borderColor: 'rgba(6, 214, 160, 1)',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

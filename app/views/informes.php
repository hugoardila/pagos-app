<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/conexion.php';

// Obtener estadísticas generales
$total_empleados = $conexion->query("SELECT COUNT(*) as total FROM empleados WHERE activo = 1")->fetch_assoc()['total'];
$total_tickets_hoy = $conexion->query("SELECT COUNT(*) as total FROM tickets WHERE DATE(fecha) = CURDATE()")->fetch_assoc()['total'];
$total_sedes = $conexion->query("SELECT COUNT(*) as total FROM sedes")->fetch_assoc()['total'];

// Obtener tasas
$tasas = [];
$res_tasas = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
while ($row = $res_tasas->fetch_assoc()) {
    $tasas[$row['moneda']] = $row['tasa'];
}

// Total de tickets por moneda (últimos 30 días)
$tickets_por_moneda = [];
$res_moneda = $conexion->query("
    SELECT moneda, COUNT(*) as cantidad, SUM(monto) as total 
    FROM tickets 
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY moneda
");
while ($row = $res_moneda->fetch_assoc()) {
    $tickets_por_moneda[] = $row;
}

// Tickets por tipo (últimos 30 días)
$tickets_por_tipo = [];
$res_tipo = $conexion->query("
    SELECT tipo, COUNT(*) as cantidad 
    FROM tickets 
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY tipo
");
while ($row = $res_tipo->fetch_assoc()) {
    $tickets_por_tipo[] = $row;
}

// Ganancias últimos 7 días
$ganancias_semana = [];
$res_semana = $conexion->query("
    SELECT DATE(ht.fecha) as fecha, SUM(ht.monto) as total, ht.moneda, ht.tipo
    FROM historico_tickets ht
    WHERE ht.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(ht.fecha), ht.moneda, ht.tipo
    ORDER BY fecha ASC
");
$datos_por_dia = [];
while ($row = $res_semana->fetch_assoc()) {
    $fecha = $row['fecha'];
    $monto = $row['total'];
    $moneda = $row['moneda'];
    $tipo = strtolower($row['tipo']);
    $tasa = $tasas[$moneda] ?? 1;
    
    $monto_cop = $monto * $tasa;
    if ($tipo === 'enganche') {
        $monto_cop /= 2;
    } elseif ($tipo === 'retaque') {
        $monto_cop *= 0.30;
    }
    
    if (!isset($datos_por_dia[$fecha])) {
        $datos_por_dia[$fecha] = 0;
    }
    $datos_por_dia[$fecha] += $monto_cop;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Informes</title>
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
            margin-bottom: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            animation: fadeInDown 0.6s ease-out;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-header h2 {
            color: #2d3748;
            font-weight: 700;
            font-size: 36px;
            margin: 0;
        }
        
        .page-header p {
            color: #718096;
            margin-top: 10px;
        }
        
        /* KPI Cards */
        .kpi-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out backwards;
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--kpi-color-1), var(--kpi-color-2));
        }
        
        .kpi-icon {
            font-size: 40px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--kpi-color-1), var(--kpi-color-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .kpi-value {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
            margin: 10px 0;
        }
        
        .kpi-label {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
        }
        
        .kpi-1 { --kpi-color-1: #667eea; --kpi-color-2: #764ba2; }
        .kpi-2 { --kpi-color-1: #06d6a0; --kpi-color-2: #118ab2; }
        .kpi-3 { --kpi-color-1: #ffd166; --kpi-color-2: #ef476f; }
        
        /* Chart Cards */
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            animation: fadeInUp 0.8s ease-out backwards;
        }
        
        .chart-card h3 {
            color: #2d3748;
            font-weight: 600;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 30px 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            text-decoration: none;
            display: block;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out backwards;
        }
        
        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--card-color-1), var(--card-color-2));
        }
        
        .report-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .report-icon {
            font-size: 50px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--card-color-1), var(--card-color-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .report-card h5 {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
        }
        
        /* Colores por módulo */
        .card-1 { --card-color-1: #667eea; --card-color-2: #764ba2; }
        .card-2 { --card-color-1: #06d6a0; --card-color-2: #118ab2; }
        .card-3 { --card-color-1: #06d6a0; --card-color-2: #118ab2; }
        .card-4 { --card-color-1: #ef476f; --card-color-2: #d62828; }
        .card-5 { --card-color-1: #ef476f; --card-color-2: #d62828; }
        .card-6 { --card-color-1: #667eea; --card-color-2: #764ba2; }
        
        /* Animaciones escalonadas */
        .col:nth-child(1) .report-card { animation-delay: 0.1s; }
        .col:nth-child(2) .report-card { animation-delay: 0.2s; }
        .col:nth-child(3) .report-card { animation-delay: 0.3s; }
        .col:nth-child(4) .report-card { animation-delay: 0.4s; }
        .col:nth-child(5) .report-card { animation-delay: 0.5s; }
        .col:nth-child(6) .report-card { animation-delay: 0.6s; }
        
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
            margin-bottom: 30px;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }
        
        .section-title {
            color: #2d3748;
            font-weight: 700;
            font-size: 24px;
            margin: 40px 0 25px;
            padding-left: 10px;
            border-left: 5px solid #667eea;
        }
        
        @media (max-width: 768px) {
            .page-header h2 {
                font-size: 28px;
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
            <h2><i class="fas fa-chart-line"></i> Módulo de Informes y Estadísticas</h2>
            <p>Visualiza el desempeño de tu negocio con gráficos y reportes</p>
        </div>

        <!-- Botón volver -->
        <a href="../index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver al Menú
        </a>

        <!-- KPIs - Estadísticas Clave -->
        <h3 class="section-title"><i class="fas fa-tachometer-alt"></i> Resumen General</h3>
        <div class="row g-4 mb-5">
        <div class="col-md-4">
                <div class="kpi-card kpi-1">
                    <div class="kpi-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="kpi-value"><?= $total_empleados ?></div>
                    <div class="kpi-label">Empleados Activos</div>
                </div>
        </div>
            
        <div class="col-md-4">
                <div class="kpi-card kpi-2">
                    <div class="kpi-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="kpi-value"><?= $total_tickets_hoy ?></div>
                    <div class="kpi-label">Tickets Hoy</div>
                </div>
        </div>
            
        <div class="col-md-4">
                <div class="kpi-card kpi-3">
                    <div class="kpi-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="kpi-value"><?= $total_sedes ?></div>
                    <div class="kpi-label">Sedes Registradas</div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <h3 class="section-title"><i class="fas fa-chart-pie"></i> Análisis Visual (Últimos 30 días)</h3>
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="chart-card">
                    <h3><i class="fas fa-money-bill-wave"></i> Tickets por Moneda</h3>
                    <canvas id="chartMoneda" height="250"></canvas>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-card">
                    <h3><i class="fas fa-tags"></i> Tickets por Tipo</h3>
                    <canvas id="chartTipo" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-area"></i> Ganancias de los Últimos 7 Días (COP)</h3>
                    <canvas id="chartGanancias" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Grid de reportes -->
        <h3 class="section-title"><i class="fas fa-file-alt"></i> Reportes Detallados</h3>
        <div class="row g-4">
        <div class="col-md-4">
                <a href="reporte_ingresos.php" class="report-card card-1">
                    <div class="report-icon">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                    <h5>Ingresos por Fechas</h5>
                </a>
            </div>

            <div class="col-md-4">
                <a href="ganancias_semanales.php" class="report-card card-2">
                    <div class="report-icon">
                        <i class="fas fa-calendar-week"></i>
                </div>
                    <h5>Ganancias Semanales - Gastos</h5>
            </a>
        </div>

        <div class="col-md-4">
                <a href="ganancias_mensuales.php" class="report-card card-3">
                    <div class="report-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h5>Ganancias por Mes - Gastos</h5>
                </a>
            </div>

            <div class="col-md-4">
                <a href="gastos_semanales.php" class="report-card card-4">
                    <div class="report-icon">
                        <i class="fas fa-money-bill-wave"></i>
                </div>
                    <h5>Gastos Semanales</h5>
            </a>
        </div>

        <div class="col-md-4">
                <a href="gastos_mensuales.php" class="report-card card-5">
                    <div class="report-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h5>Gastos Mensuales</h5>
                </a>
            </div>

            <div class="col-md-4">
                <a href="ganancias_por_sede.php" class="report-card card-6">
                    <div class="report-icon">
                        <i class="fas fa-building"></i>
                </div>
                    <h5>Ganancias por Sede</h5>
            </a>
        </div>
    </div>
    </div>

    <script>
    // Gráfico de Tickets por Moneda
    const dataMoneda = <?= json_encode($tickets_por_moneda) ?>;
    const ctxMoneda = document.getElementById('chartMoneda').getContext('2d');
    new Chart(ctxMoneda, {
        type: 'doughnut',
        data: {
            labels: dataMoneda.map(d => d.moneda),
            datasets: [{
                label: 'Cantidad',
                data: dataMoneda.map(d => d.cantidad),
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(6, 214, 160, 0.8)',
                    'rgba(255, 209, 102, 0.8)',
                    'rgba(239, 71, 111, 0.8)'
                ],
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14,
                            family: "'Segoe UI', sans-serif"
                        },
                        padding: 15
                    }
                }
            }
        }
    });

    // Gráfico de Tickets por Tipo
    const dataTipo = <?= json_encode($tickets_por_tipo) ?>;
    const ctxTipo = document.getElementById('chartTipo').getContext('2d');
    new Chart(ctxTipo, {
        type: 'pie',
        data: {
            labels: dataTipo.map(d => d.tipo.charAt(0).toUpperCase() + d.tipo.slice(1)),
            datasets: [{
                label: 'Cantidad',
                data: dataTipo.map(d => d.cantidad),
                backgroundColor: [
                    'rgba(6, 214, 160, 0.8)',
                    'rgba(239, 71, 111, 0.8)'
                ],
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14,
                            family: "'Segoe UI', sans-serif"
                        },
                        padding: 15
                    }
                }
            }
        }
    });

    // Gráfico de Ganancias Últimos 7 Días
    const datosGanancias = <?= json_encode($datos_por_dia) ?>;
    const fechas = Object.keys(datosGanancias);
    const valores = Object.values(datosGanancias);
    
    const ctxGanancias = document.getElementById('chartGanancias').getContext('2d');
    new Chart(ctxGanancias, {
        type: 'line',
        data: {
            labels: fechas,
            datasets: [{
                label: 'Ganancias (COP)',
                data: valores,
                backgroundColor: 'rgba(102, 126, 234, 0.2)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(102, 126, 234, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
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
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
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

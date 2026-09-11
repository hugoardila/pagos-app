<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../includes/conexion.php';

// === FILTRO TARJETA 1: GANANCIAS ===
if (isset($_POST['filtrar_ganancias'])) {
    $fecha_inicio1 = $_POST['fecha_inicio1'];
    $hora_inicio1 = $_POST['hora_inicio1'];
    $fecha_fin1 = $_POST['fecha_fin1'];
    $hora_fin1 = $_POST['hora_fin1'];
} else {
    $fecha_inicio1 = date('Y-m-d', strtotime('-7 days'));
    $hora_inicio1 = '00:00';
    $fecha_fin1 = date('Y-m-d');
    $hora_fin1 = '23:59';
}
$desde1 = "$fecha_inicio1 $hora_inicio1:00";
$hasta1 = "$fecha_fin1 $hora_fin1:59";

// === FILTRO TARJETA 2: GASTOS ===
if (isset($_POST['filtrar_gastos'])) {
    $fecha_inicio2 = $_POST['fecha_inicio2'];
    $hora_inicio2 = $_POST['hora_inicio2'];
    $fecha_fin2 = $_POST['fecha_fin2'];
    $hora_fin2 = $_POST['hora_fin2'];
} else {
    $fecha_inicio2 = date('Y-m-d', strtotime('-7 days'));
    $hora_inicio2 = '00:00';
    $fecha_fin2 = date('Y-m-d');
    $hora_fin2 = '23:59';
}
$desde2 = "$fecha_inicio2 $hora_inicio2:00";
$hasta2 = "$fecha_fin2 $hora_fin2:59";

// === CONSULTAS SQL ===
$sql1 = "SELECT ht.*, e.nombre AS nombre_empleado
         FROM historico_tickets ht
         LEFT JOIN empleados e ON ht.id_empleado = e.id
         WHERE ht.fecha BETWEEN '$desde1' AND '$hasta1'
         ORDER BY ht.fecha DESC";
$res1 = mysqli_query($conexion, $sql1);

$sql2 = "SELECT * FROM gastos_oficina
         WHERE fecha BETWEEN '$desde2' AND '$hasta2'
         ORDER BY fecha DESC";
$res2 = mysqli_query($conexion, $sql2);

// === TASAS DE CAMBIO ===
$tasas = [];
$q_tasas = mysqli_query($conexion, "SELECT moneda, tasa FROM tasas_cambio");
while ($row = mysqli_fetch_assoc($q_tasas)) {
    $tasas[$row['moneda']] = $row['tasa'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganancias Semanales</title>
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
        .data-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .card-header-custom {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .card-header-custom h3 {
            color: #2d3748;
            font-weight: 600;
            margin: 0;
            font-size: 20px;
        }
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 8px 12px;
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
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 600;
        }
        .table-custom {
            width: 100%;
        }
        .table-custom thead {
            background: linear-gradient(135deg, #f8f9ff 0%, #f5f7fa 100%);
        }
        .table-custom thead th {
            padding: 12px;
            color: #2d3748;
            font-weight: 600;
            border-bottom: 2px solid #667eea;
        }
        .table-custom tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .total-badge {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 18px;
            display: inline-block;
            margin-top: 15px;
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
            <h2><i class="fas fa-calendar-week"></i> Ganancias y Gastos Semanales</h2>
        </div>

        <a href="informes.php" class="btn-back" style="margin-bottom: 20px;">
            <i class="fas fa-arrow-left"></i> Volver
        </a>

        <div class="row">
            <!-- TARJETA 1: GANANCIAS -->
            <div class="col-lg-6">
                <div class="data-card">
                    <div class="card-header-custom">
                        <h3><i class="fas fa-chart-line"></i> Ganancias</h3>
                    </div>
                    
                    <form method="POST" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 600; font-size: 13px;">Desde:</label>
                                <input type="date" name="fecha_inicio1" value="<?= $fecha_inicio1 ?>" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 600; font-size: 13px;">Hora:</label>
                                <input type="time" name="hora_inicio1" value="<?= $hora_inicio1 ?>" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 600; font-size: 13px;">Hasta:</label>
                                <input type="date" name="fecha_fin1" value="<?= $fecha_fin1 ?>" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 600; font-size: 13px;">Hora:</label>
                                <input type="time" name="hora_fin1" value="<?= $hora_fin1 ?>" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="filtrar_ganancias" class="btn-filter w-100">
                                    <i class="fas fa-filter"></i> Filtrar Ganancias
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Monto</th>
                                    <th>Moneda</th>
                                    <th>Tipo</th>
                                    <th>COP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_ganancias = 0;
                                $total_cop = 0;

                                if ($res1 && mysqli_num_rows($res1) > 0) {
                                    while ($row = mysqli_fetch_assoc($res1)) {
                                        $monto = $row['monto'];
                                        $moneda = $row['moneda'];
                                        $tipo = strtolower($row['tipo']);
                                        $tasa = $tasas[$moneda] ?? 1;
                                        $monto_cop = $monto * $tasa;

                                        if ($tipo === 'enganche') {
                                            $monto_cop /= 2;
                                        } elseif ($tipo === 'retaque') {
                                            $monto_cop *= 0.30;
                                        }

                                        echo "<tr>";
                                        echo "<td>" . number_format($monto, 0, ',', '.') . "</td>";
                                        echo "<td>$moneda</td>";
                                        echo "<td>" . ucfirst($tipo) . "</td>";
                                        echo "<td style='color: #06d6a0; font-weight: 700;'>" . number_format($monto_cop, 0, ',', '.') . "</td>";
                                        echo "</tr>";

                                        $total_ganancias += $monto;
                                        $total_cop += $monto_cop;
                                    }
                                } else {
                                    echo "<tr><td colspan='4' style='text-align: center; color: #718096;'>Sin resultados</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="text-align: center;">
                        <span class="total-badge">
                            <i class="fas fa-dollar-sign"></i> Total: $<?= number_format($total_cop, 0, ',', '.') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- TARJETA 2: GASTOS -->
            <div class="col-lg-6">
                <div class="data-card">
                    <div class="card-header-custom">
                        <h3><i class="fas fa-money-bill-wave"></i> Gastos</h3>
                    </div>
                    
                    <form method="POST" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 600; font-size: 13px;">Desde:</label>
                                <input type="date" name="fecha_inicio2" value="<?= $fecha_inicio2 ?>" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 600; font-size: 13px;">Hora:</label>
                                <input type="time" name="hora_inicio2" value="<?= $hora_inicio2 ?>" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 600; font-size: 13px;">Hasta:</label>
                                <input type="date" name="fecha_fin2" value="<?= $fecha_fin2 ?>" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight: 600; font-size: 13px;">Hora:</label>
                                <input type="time" name="hora_fin2" value="<?= $hora_fin2 ?>" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="filtrar_gastos" class="btn-filter w-100">
                                    <i class="fas fa-filter"></i> Filtrar Gastos
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Descripción</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_gastos = 0;
                                if ($res2 && mysqli_num_rows($res2) > 0) {
                                    while ($row = mysqli_fetch_assoc($res2)) {
                                        $total_gastos += $row['monto'];
                                        echo "<tr>";
                                        echo "<td>{$row['descripcion']}</td>";
                                        echo "<td style='color: #ef476f; font-weight: 700;'>" . number_format($row['monto'], 0, ',', '.') . "</td>";
                                        echo "<td>" . date('Y-m-d', strtotime($row['fecha'])) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' style='text-align: center; color: #718096;'>Sin resultados</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="text-align: center;">
                        <span class="total-badge" style="background: linear-gradient(135deg, #ef476f 0%, #d62828 100%);">
                            <i class="fas fa-dollar-sign"></i> Total: $<?= number_format($total_gastos, 0, ',', '.') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

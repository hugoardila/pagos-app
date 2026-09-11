<?php
include '../includes/conexion.php';

// AJAX: devolver solo el detalle
if (isset($_GET['detalle']) && $_GET['detalle'] === '1' && isset($_GET['id_empleado'], $_GET['fecha'])) {
    $id_empleado = intval($_GET['id_empleado']);
    $fecha = $_GET['fecha'];

    $tasas = [];
    $res_tasas = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
    while ($row = $res_tasas->fetch_assoc()) {
        $tasas[strtoupper($row['moneda'])] = $row['tasa'];
    }

    $nombre = 'Empleado';
    $res_emp = $conexion->query("SELECT nombre FROM empleados WHERE id = $id_empleado LIMIT 1");
    if ($res_emp && $emp = $res_emp->fetch_assoc()) {
        $nombre = $emp['nombre'];
    }

    $detalle = ['enganche' => [], 'retaque' => []];
    $total_cop = 0;

    $res_tickets = $conexion->query("SELECT tipo, moneda, monto FROM historico_tickets WHERE id_empleado = $id_empleado AND DATE(fecha) = '$fecha'");
    while ($row = $res_tickets->fetch_assoc()) {
        $tipo = strtolower($row['tipo']);
        $moneda = strtoupper($row['moneda']);
        $monto = floatval($row['monto']);
        $tasa = $tasas[$moneda] ?? 1;

        if ($tipo === 'enganche') {
            $valor_cop = ($monto * $tasa) / 2;
        } elseif ($tipo === 'retaque') {
            $valor_cop = ($monto * $tasa) * 0.30;
        } elseif ($moneda === 'COP') {
            $valor_cop = $monto;
        } else {
            continue;
        }

        $detalle[$tipo][$moneda][] = [
            'monto' => $monto,
            'valor_cop' => $valor_cop
        ];
        $total_cop += $valor_cop;
    }

    echo "<div style='padding: 10px;'>";
    echo "<strong style='color: #2d3748; font-size: 16px;'>Empleado:</strong> <span style='color: #667eea;'>" . strtoupper($nombre) . "</span><br>";
    echo "<strong style='color: #2d3748; font-size: 16px;'>Fecha:</strong> <span style='color: #667eea;'>" . $fecha . "</span><hr style='border-color: #e0e0e0;'>";

    foreach (['enganche' => 'Enganches', 'retaque' => 'Retaques'] as $tipo => $titulo) {
        if (!empty($detalle[$tipo])) {
            echo "<strong style='color: #2d3748;'>$titulo:</strong><br>";
            foreach ($detalle[$tipo] as $moneda => $items) {
                echo "<u style='color: #667eea;'>$moneda</u><br>";
                foreach ($items as $item) {
                    echo "<span style='color: #718096;'>" . number_format($item['monto'], 0, ',', '.') . " → " . number_format($item['valor_cop'], 0, ',', '.') . " COP</span><br>";
                }
            }
        }
    }

    echo "<hr style='border-color: #e0e0e0;'><strong style='color: #2d3748; font-size: 16px;'>Total COP:</strong> <span style='color: #06d6a0; font-size: 18px; font-weight: 700;'>$" . number_format($total_cop, 0, ',', '.') . "</span>";

    if ($total_cop > 110000) {
        echo "<br><span style='color: #ef476f;'>Descuento aseo: -$10.000</span>";
        echo "<br><strong style='color: #2d3748;'>Total a pagar: <span style='color: #06d6a0; font-size: 18px;'>$" . number_format($total_cop - 10000, 0, ',', '.') . "</span></strong>";
    }
    echo "</div>";
    exit;
}

// Código principal
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$nombre = $_GET['nombre'] ?? '';

$tasas = [];
$res_tasas = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
while ($row = $res_tasas->fetch_assoc()) {
    $tasas[$row['moneda']] = floatval($row['tasa']);
}

$query = "SELECT h.id_empleado, e.nombre, h.fecha, h.tipo, h.moneda, h.monto
          FROM historico_tickets h
          JOIN empleados e ON h.id_empleado = e.id
          WHERE 1";

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $query .= " AND h.fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
}

if (!empty($nombre)) {
    $query .= " AND e.nombre LIKE '%$nombre%'";
}

$query .= " ORDER BY h.fecha DESC";

$res = $conexion->query($query);

$pagos = [];
while ($row = $res->fetch_assoc()) {
    $id = $row['id_empleado'];
    $nombre_empleado = $row['nombre'];
    $fecha = date('Y-m-d', strtotime($row['fecha']));
    $tipo = $row['tipo'];
    $moneda = $row['moneda'];
    $monto = floatval($row['monto']);

    $tasa = $tasas[$moneda] ?? 1;
    $ganancia = 0;

    if ($tipo === 'enganche') {
        $ganancia = ($monto * $tasa) / 2;
    } elseif ($tipo === 'retaque') {
        $ganancia = ($monto * $tasa) * 0.30;
    } elseif ($moneda === 'COP') {
        $ganancia = $monto;
    }

    $clave = $id . '_' . $fecha;
    if (!isset($pagos[$clave])) {
        $pagos[$clave] = [
            'id' => $id,
            'nombre' => $nombre_empleado,
            'fecha' => $fecha,
            'total_pagado' => 0
        ];
    }
    $pagos[$clave]['total_pagado'] += $ganancia;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pagos</title>
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
            margin: 0 0 10px 0;
        }
        
        .page-header p {
            color: #718096;
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
            padding: 15px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background: #f8f9ff;
        }
        
        .btn-detail {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .btn-detail:hover {
            background: #667eea;
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
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
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
            <h2><i class="fas fa-history"></i> Historial de Pagos</h2>
            <p>Consulta el historial de pagos por jornada</p>
        </div>

        <!-- Botón volver -->
        <a href="../index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver
        </a>

        <!-- Filtros -->
        <div class="filter-card">
            <h5 style="margin-bottom: 20px; color: #2d3748; font-weight: 600;">
                <i class="fas fa-filter"></i> Filtros de Búsqueda
            </h5>
            <form class="row g-3" method="GET">
                <div class="col-md-3">
                    <label class="form-label-custom">
                        <i class="fas fa-calendar-alt"></i> Fecha Inicio
                    </label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label-custom">
                        <i class="fas fa-calendar-check"></i> Fecha Fin
                    </label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label-custom">
                        <i class="fas fa-user"></i> Nombre del Empleado
                    </label>
                    <input type="text" name="nombre" class="form-control" placeholder="Buscar por nombre" value="<?= htmlspecialchars($nombre) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-filter w-100">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Empleado</th>
                        <th><i class="fas fa-money-bill-wave"></i> Total Pagado (COP)</th>
                        <th><i class="fas fa-calendar"></i> Fecha</th>
                        <th><i class="fas fa-info-circle"></i> Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pagos) > 0): ?>
                        <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pago['nombre']) ?></strong></td>
                            <td style="color: #06d6a0; font-weight: 700; font-size: 16px;">
                                $<?= number_format($pago['total_pagado'], 0, ',', '.') ?>
                            </td>
                            <td><?= $pago['fecha'] ?></td>
                            <td>
                                <button class="btn-detail ver-detalle-btn"
                                        data-id="<?= $pago['id'] ?>"
                                        data-fecha="<?= $pago['fecha'] ?>">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px; color: #718096;">
                                <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                                <p style="margin: 0;">No se encontraron pagos con los filtros seleccionados</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-invoice"></i> Detalle de Jornada
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="contenido-detalle">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('.ver-detalle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const fecha = btn.dataset.fecha;
            const contenedor = document.getElementById('contenido-detalle');
            const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));

            contenedor.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

            fetch(`historial_pagos.php?detalle=1&id_empleado=${id}&fecha=${fecha}`)
                .then(r => r.text())
                .then(html => {
                    contenedor.innerHTML = html;
                    modal.show();
                })
                .catch(() => {
                    contenedor.innerHTML = '<div class="text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar el detalle.</div>';
                });
        });
    });
    </script>
</body>
</html>

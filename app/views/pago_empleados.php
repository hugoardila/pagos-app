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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['tasas'])) {
    foreach ($_POST['tasas'] as $moneda => $tasa) {
        $moneda = $conexion->real_escape_string($moneda);
        $tasa = floatval($tasa);
        $conexion->query("UPDATE tasas_cambio SET tasa = $tasa WHERE moneda = '$moneda'");
    }
    header("Location: pago_empleados.php");
    exit();
}

$tasas = [];
$result = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
while ($row = $result->fetch_assoc()) {
    $tasas[$row['moneda']] = $row['tasa'];
}

$empleados = [];
$sql_empleados = "SELECT e.id, e.nombre FROM empleados e WHERE e.activo = 1";
$res_emp = $conexion->query($sql_empleados);
while ($emp = $res_emp->fetch_assoc()) {
    $id_empleado = $emp['id'];
    $total = 0;
    $sql_tickets = "SELECT tipo, moneda, monto FROM tickets WHERE id_empleado = $id_empleado";
    $res_tickets = $conexion->query($sql_tickets);
    while ($ticket = $res_tickets->fetch_assoc()) {
        $tipo = $ticket['tipo'];
        $moneda = $ticket['moneda'];
        $monto = floatval($ticket['monto']);
        $tasa = isset($tasas[$moneda]) ? $tasas[$moneda] : 1;
        if ($tipo === 'enganche') {
            $total += ($monto * $tasa) / 2;
        } elseif ($tipo === 'retaque') {
            $total += ($monto * $tasa) * 0.30;
        } elseif ($moneda === 'COP') {
            $total += $monto;
        }
    }
    if ($total > 0) {
        $empleados[] = [
            'id' => $id_empleado,
            'nombre' => $emp['nombre'],
            'total' => $total
        ];
    }
}
$finalizado = isset($_GET['finalizado']) && $_GET['finalizado'] == 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de Empleados</title>
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
        
        .tasas-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .tasas-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .tasa-group {
            flex: 1;
            min-width: 150px;
        }
        
        .tasa-group label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-control-custom {
            height: 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 0 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-control-custom:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .btn-update {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            height: 45px;
            transition: all 0.3s ease;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
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
        
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            background: #d4edda;
            color: #155724;
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
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
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: #f8f9ff;
        }
        
        .pagado {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
        }
        
        .total-amount {
            font-size: 20px;
            font-weight: 700;
            color: #06d6a0;
        }
        
        .btn-pagar {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-pagar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 214, 160, 0.4);
            color: white;
        }
        
        .btn-pagar:disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-imprimir {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-imprimir:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-finalizar {
            background: linear-gradient(135deg, #ef476f 0%, #d62828 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-finalizar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 71, 111, 0.4);
            color: white;
        }
        
        .actions-footer {
            text-align: right;
            padding: 0 10px;
        }
        
        @media (max-width: 768px) {
            .tasas-form {
                flex-direction: column;
            }
            
            .tasa-group {
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
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <h2><i class="fas fa-wallet"></i> Pago de Empleados</h2>
                <a href="../index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <!-- Tasas de cambio -->
        <div class="tasas-card" id="tasas-cambio">
            <h5 style="margin-bottom: 20px; color: #2d3748; font-weight: 600;">
                <i class="fas fa-exchange-alt"></i> Tasas de Cambio (Editable)
            </h5>
            <form method="POST" class="tasas-form">
                <?php foreach ($tasas as $moneda => $tasa): ?>
                    <div class="tasa-group">
                        <label>
                            <i class="fas fa-dollar-sign"></i> Tasa <?= $moneda ?>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               name="tasas[<?= $moneda ?>]" 
                               value="<?= $tasa ?>" 
                               class="form-control form-control-custom" 
                               <?= $moneda === 'COP' ? 'readonly' : '' ?>>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-update">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </form>
        </div>

        <!-- Alerta de éxito -->
        <?php if ($finalizado): ?>
            <div class="alert-custom">
                <i class="fas fa-check-circle fa-lg"></i>
                <span>¡Jornada de pagos finalizada correctamente!</span>
            </div>
        <?php endif; ?>

        <!-- Tabla de empleados -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Empleado</th>
                        <th><i class="fas fa-money-bill-wave"></i> Total a Pagar (COP)</th>
                        <th><i class="fas fa-cogs"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($empleados) > 0): ?>
                        <?php foreach ($empleados as $emp): ?>
                            <tr id="empleado-<?= $emp['id'] ?>">
                                <td><strong><?= htmlspecialchars($emp['nombre']) ?></strong></td>
                                <td>
                                    <span class="total-amount">
                                        $<?= number_format($emp['total'], 0, ',', '.') ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-pagar" onclick="marcarPagado(<?= $emp['id'] ?>, this)">
                                        <i class="fas fa-check-circle"></i> Pagar
                                    </button>
                                    <a href="ticket.php?id_empleado=<?= $emp['id'] ?>" 
                                       target="_blank" 
                                       class="btn-imprimir">
                                        <i class="fas fa-print"></i> Imprimir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px; color: #718096;">
                                <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                                <p style="margin: 0;">No hay empleados con pagos pendientes</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Botón finalizar jornada -->
        <div class="actions-footer">
            <a href="finalizar_jornada.php" class="btn btn-finalizar">
                <i class="fas fa-flag-checkered"></i> Finalizar Jornada de Pagos
            </a>
        </div>
    </div>

    <script>
    function marcarPagado(id, btn) {
        const fila = document.getElementById('empleado-' + id);
        fila.classList.add('pagado');
        btn.innerHTML = '<i class="fas fa-check-double"></i> Pagado';
        btn.disabled = true;
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

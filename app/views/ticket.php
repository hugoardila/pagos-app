
<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id_empleado'])) {
    header("Location: ../index.php");
    exit();
}

$id_empleado = intval($_GET['id_empleado']);
$conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$res_emp = $conexion->query("SELECT nombre FROM empleados WHERE id = $id_empleado LIMIT 1");
$nombre = $res_emp->fetch_assoc()['nombre'] ?? 'Empleado';

$tasas = [];
$res_tasas = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
while ($row = $res_tasas->fetch_assoc()) {
    $tasas[strtoupper($row['moneda'])] = $row['tasa'];
}

$detalle = ['enganche' => [], 'retaque' => []];
$total_cop = 0;

$res_tickets = $conexion->query("SELECT tipo, moneda, monto FROM tickets WHERE id_empleado = $id_empleado");
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

$fecha = date("Y-m-d H:i:s");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket</title>
    <style>
        body {
            font-family: monospace;
            font-size: 12px;
            margin: 0;
            padding: 5px;
            line-height: 1;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 50px;
            left: 0;
            width: 100%;
            text-align: center;
            z-index: 0;
        }

        .watermark img {
            width: 140px;
            opacity: 0.04;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 4px 0; }
        .detalle { margin-left: 10px; }
        .item-line { margin: 0; padding: 0; white-space: pre; }

        .resaltado-descuento {
        color: red;
        font-weight: bold;
        font-size: 13px; }

        .total-final {
        font-weight: bold;
        font-size: 14px;
        text-decoration: underline; }

        .linea-doble {
        border-top: 3px double #000;
        margin: 6px 0; }
    </style>
</head>
<body onload="window.print()">

    <!-- Logo de fondo dentro del flujo y detrás del texto -->
    <div class="watermark">
        <img src="/img/logo.png" alt="Logo">
    </div>

    <div class="content">
        <div class="center">
            <strong>TECNOXPERT</strong><br>
            NIT: 1083903212-3<br>
            
        </div>
        <div class="line"></div>
        <strong>Empleado 🧑‍🏭:</strong> <?= strtoupper($nombre) ?><br>
        <strong>Fecha:</strong> <?= $fecha ?><br>
        <div class="line"></div>

        <?php foreach (['enganche' => 'Enganches', 'retaque' => 'Retaques'] as $tipo => $titulo): ?>
            <?php if (!empty($detalle[$tipo])): ?>
                <strong><?= $titulo ?>:</strong><br>
                <?php foreach ($detalle[$tipo] as $moneda => $items): ?>
                    <?= str_pad($moneda, 10, ' ', STR_PAD_LEFT) ?><br>
                    <?php foreach ($items as $item): ?>
<div class="item-line"><?= str_pad(number_format($item['monto'], 0, ',', '.'), 6, ' ', STR_PAD_LEFT) ?> ------> <?= number_format($item['valor_cop'], 0, ',', '.') ?> COP</div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="line"></div>
        <div class="center">
            <strong>TOTAL EN COP: $<?= number_format($total_cop, 0, ',', '.') ?></strong>
        </div>
        <?php if ($total_cop > 110000): ?>
        <div class="linea-doble"></div>
        <div class="center">
            <div class="resaltado-descuento">ASEO 🧹: -$<?= number_format(10000, 0, ',', '.') ?></div>
            <div style="height: 6px;"></div> <!-- Espacio vertical -->
            <div class="total-final">✅ PAGAR: $<?= number_format($total_cop - 10000, 0, ',', '.') ?> COP</div>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>

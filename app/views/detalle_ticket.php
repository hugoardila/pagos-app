<?php
if (!isset($_GET['id_empleado']) || !isset($_GET['fecha'])) {
    echo "<p>Datos incompletos.</p>";
    exit();
}

$conexion = new mysqli(getenv('PAGOS_APP_DB_HOST') ?: '', getenv('PAGOS_APP_DB_USER') ?: '', getenv('PAGOS_APP_DB_PASS') ?: '', getenv('PAGOS_APP_DB_NAME') ?: '');
if ($conexion->connect_error) {
    die("<p>Error de conexión.</p>");
}

$id_empleado = intval($_GET['id_empleado']);
$fecha = $conexion->real_escape_string($_GET['fecha']);
$fecha_sin_hora = substr($fecha, 0, 10);

$tasas = [];
$res_tasas = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
while ($row = $res_tasas->fetch_assoc()) {
    $tasas[$row['moneda']] = $row['tasa'];
}

$res_nombre = $conexion->query("SELECT nombre FROM empleados WHERE id = $id_empleado");
$nombre = ($row = $res_nombre->fetch_assoc()) ? $row['nombre'] : 'Desconocido';

$res_tickets = $conexion->query("SELECT descripcion, monto, moneda, tipo FROM historico_tickets WHERE id_empleado = $id_empleado AND DATE(fecha) = '$fecha_sin_hora'");
$tickets = [];
while ($row = $res_tickets->fetch_assoc()) {
    $tickets[] = $row;
}

$detalle = [];
$total_cop = 0;
foreach ($tickets as $ticket) {
    $tipo = strtoupper($ticket['tipo']);
    $moneda = strtoupper($ticket['moneda']);
    $clave = "$tipo ($moneda)";
    $monto = floatval($ticket['monto']);
    $tasa = $tasas[$ticket['moneda']] ?? 1;

    if ($ticket['tipo'] === 'enganche') {
        $valor_cop = ($monto * $tasa) / 2;
    } elseif ($ticket['tipo'] === 'retaque') {
        $valor_cop = ($monto * $tasa) * 0.30;
    } else {
        $valor_cop = $monto * $tasa;
    }

    $detalle[$clave][] = [
        'monto' => $monto,
        'moneda' => $moneda,
        'valor_cop' => $valor_cop
    ];
    $total_cop += $valor_cop;
}
?>
<div class="modal-header">
    <h5 class="modal-title">Detalle de Pago - <?= htmlspecialchars($nombre) ?> (<?= $fecha ?>)</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>
<div class="modal-body">
    <?php foreach ($detalle as $clave => $valores): ?>
        <h6 class="fw-bold mt-3"><?= $clave ?></h6>
        <ul>
            <?php foreach ($valores as $valor): ?>
                <li><?= number_format($valor['monto'], 0, ',', '.') ?> <?= $valor['moneda'] ?> → $<?= number_format($valor['valor_cop'], 0, ',', '.') ?> COP</li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
    <div class="mt-3">
        <strong>Total: </strong> $<?= number_format($total_cop, 0, ',', '.') ?> COP
    </div>
</div>

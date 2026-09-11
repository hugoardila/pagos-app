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

$fecha_actual = date("Y-m-d H:i:s");

// Obtener tasas
$tasas = [];
$res_tasas = $conexion->query("SELECT moneda, tasa FROM tasas_cambio");
while ($row = $res_tasas->fetch_assoc()) {
    $tasas[$row['moneda']] = $row['tasa'];
}

// Obtener todos los empleados únicos con tickets
$sql = "SELECT id_empleado FROM tickets GROUP BY id_empleado";
$res_empleados = $conexion->query($sql);

while ($row = $res_empleados->fetch_assoc()) {
    $id_empleado = $row['id_empleado'];

    $res_tickets = $conexion->query("SELECT * FROM tickets WHERE id_empleado = $id_empleado");
    $total_cop = 0;

    while ($ticket = $res_tickets->fetch_assoc()) {
        $tipo = $ticket['tipo'];
        $moneda = $ticket['moneda'];
        $monto = floatval($ticket['monto']);
        $tasa = $tasas[$moneda] ?? 1;

        if ($tipo === 'enganche') {
            $valor_cop = ($monto * $tasa) / 2;
        } elseif ($tipo === 'retaque') {
            $valor_cop = ($monto * $tasa) * 0.30;
        } else {
            $valor_cop = $monto * $tasa;
        }

        $total_cop += $valor_cop;

        // Insertar en historico_tickets
        $stmt = $conexion->prepare("INSERT INTO historico_tickets (id_empleado, descripcion, monto, moneda, tipo, fecha) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsss", $ticket['id_empleado'], $ticket['descripcion'], $ticket['monto'], $ticket['moneda'], $ticket['tipo'], $fecha_actual);
        $stmt->execute();
    }

    // Insertar total por empleado en historial_de_pagos
    $stmt = $conexion->prepare("INSERT INTO historial_de_pagos (id_empleado, total_pagado, fecha) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $id_empleado, $total_cop, $fecha_actual);
    $stmt->execute();
}

// Eliminar todos los tickets (ya procesados)
$conexion->query("DELETE FROM tickets");

header("Location: ../index.php");
exit();

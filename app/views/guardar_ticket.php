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

$ids = $_POST['id_empleado'] ?? [];
$descripciones = $_POST['descripcion'] ?? [];
$montos = $_POST['monto'] ?? [];
$monedas = $_POST['moneda'] ?? [];
$tipos = $_POST['tipo'] ?? [];

// Compatibilidad: si llega 1 ticket en formato antiguo, normalizar a arreglos
if (!is_array($ids)) $ids = [$ids];
if (!is_array($descripciones)) $descripciones = [$descripciones];
if (!is_array($montos)) $montos = [$montos];
if (!is_array($monedas)) $monedas = [$monedas];
if (!is_array($tipos)) $tipos = [$tipos];

$total_filas = count($ids);
if ($total_filas === 0) {
    echo "No se recibieron tickets para registrar.";
    $conexion->close();
    exit();
}

$stmt_insert = $conexion->prepare("INSERT INTO tickets (id_empleado, descripcion, monto, moneda, tipo, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
if (!$stmt_insert) {
    echo "Error al preparar inserción: " . $conexion->error;
    $conexion->close();
    exit();
}

$stmt_sede = $conexion->prepare("SELECT id_sede FROM empleados WHERE id = ?");
$stmt_retacador = $conexion->prepare("SELECT id FROM empleados WHERE id_sede = ? AND rol_id = ? LIMIT 1");

$retacador_rol = null;
$rol_q = $conexion->query("SELECT id FROM rol_empleado WHERE LOWER(nombre) = 'retacador' LIMIT 1");
if ($rol_q && $rol_q->num_rows > 0) {
    $retacador_rol = intval($rol_q->fetch_assoc()['id']);
}

$cache_retacador_por_sede = [];
$insertados = 0;

for ($i = 0; $i < $total_filas; $i++) {
    $id_empleado = intval($ids[$i] ?? 0);
    $descripcion = trim((string)($descripciones[$i] ?? ''));
    $monto_raw = $montos[$i] ?? '';
    $moneda = strtoupper(trim((string)($monedas[$i] ?? '')));
    $tipo = strtolower(trim((string)($tipos[$i] ?? '')));

    if (
        $id_empleado <= 0 ||
        $descripcion === '' ||
        !is_numeric($monto_raw) ||
        !in_array($moneda, ['COP', 'USD', 'MXN', 'QZ'], true) ||
        !in_array($tipo, ['enganche', 'retaque'], true)
    ) {
        continue;
    }

    $monto = floatval($monto_raw);

    $stmt_insert->bind_param("isdss", $id_empleado, $descripcion, $monto, $moneda, $tipo);
    if (!$stmt_insert->execute()) {
        continue;
    }
    $insertados++;

    // Si es retaque, duplicar para retacador de la misma sede
    if ($tipo === 'retaque' && $retacador_rol !== null && $stmt_sede && $stmt_retacador) {
        $stmt_sede->bind_param("i", $id_empleado);
        $stmt_sede->execute();
        $res_sede = $stmt_sede->get_result();
        $row_sede = $res_sede ? $res_sede->fetch_assoc() : null;
        $sede = isset($row_sede['id_sede']) ? intval($row_sede['id_sede']) : 0;

        if ($sede > 0) {
            if (!array_key_exists($sede, $cache_retacador_por_sede)) {
                $stmt_retacador->bind_param("ii", $sede, $retacador_rol);
                $stmt_retacador->execute();
                $res_ret = $stmt_retacador->get_result();
                $row_ret = $res_ret ? $res_ret->fetch_assoc() : null;
                $cache_retacador_por_sede[$sede] = $row_ret ? intval($row_ret['id']) : 0;
            }

            $retacador_id = $cache_retacador_por_sede[$sede];
            if ($retacador_id > 0) {
                $stmt_insert->bind_param("isdss", $retacador_id, $descripcion, $monto, $moneda, $tipo);
                $stmt_insert->execute();
            }
        }
    }
}

$stmt_insert->close();
if ($stmt_sede) $stmt_sede->close();
if ($stmt_retacador) $stmt_retacador->close();

if ($insertados > 0) {
    header("Location: registro_tickets.php?exito=1&cantidad=" . $insertados);
    exit();
}

echo "No se pudo registrar ningún ticket. Verifica los datos ingresados.";

$conexion->close();
?>

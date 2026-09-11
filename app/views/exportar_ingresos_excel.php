<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../includes/conexion.php';

$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$sede = $_GET['sede'] ?? '';
$empleado = $_GET['empleado'] ?? '';
$tipo_ticket = $_GET['tipo_ticket'] ?? '';

$where = [];
if ($fecha_inicio && $fecha_fin) {
    $where[] = "tickets.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if ($sede !== '') {
    $where[] = "sedes.nombre = '$sede'";
}
if ($empleado !== '') {
    $where[] = "empleados.nombre = '$empleado'";
}
if ($tipo_ticket !== '') {
    $where[] = "tickets.tipo = '$tipo_ticket'";
}

$condiciones = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "SELECT tickets.fecha, empleados.nombre AS nombre_empleado, tickets.tipo, tickets.moneda, tickets.monto, sedes.nombre AS sede
          FROM tickets
          JOIN empleados ON tickets.id_empleado = empleados.id
          JOIN sedes ON empleados.id_sede = sedes.id
          $condiciones
          ORDER BY tickets.fecha DESC";
$resultado = $conexion->query($query);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Ingresos Filtrados');

$encabezados = ['Fecha', 'Sede', 'Empleado', 'Tipo', 'Moneda', 'Monto'];
$sheet->fromArray($encabezados, NULL, 'A1');

$fila = 2;
while ($row = $resultado->fetch_assoc()) {
    $sheet->setCellValue("A$fila", $row['fecha']);
    $sheet->setCellValue("B$fila", $row['sede']);
    $sheet->setCellValue("C$fila", $row['nombre_empleado']);
    $sheet->setCellValue("D$fila", ucfirst($row['tipo']));
    $sheet->setCellValue("E$fila", $row['moneda']);
    $sheet->setCellValue("F$fila", $row['monto']);
    $fila++;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ingresos_filtrados.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();

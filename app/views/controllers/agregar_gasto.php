<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = trim($_POST['descripcion']);
    $monto = floatval($_POST['monto']);
    $fecha = $_POST['fecha'];
    $id_sede = intval($_POST['id_sede']);

    if (!empty($descripcion) && $monto > 0 && !empty($fecha) && $id_sede > 0) {
        $stmt = $conexion->prepare("INSERT INTO gastos_oficina (descripcion, monto, fecha, id_sede) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sdsi", $descripcion, $monto, $fecha, $id_sede);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: ../gastos_semanales.php?exito=1");
        exit();
    } else {
        header("Location: ../gastos_semanales.php?error=1");
        exit();
    }
}

<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $id_empleado = intval($_POST['id_empleado']);
    $descripcion = $conexion->real_escape_string($_POST['descripcion']);
    $monto = floatval($_POST['monto']);
    $moneda = $conexion->real_escape_string($_POST['moneda']);
    $tipo = $conexion->real_escape_string($_POST['tipo']);

    $query = "UPDATE tickets 
          SET descripcion='$descripcion', monto=$monto, moneda='$moneda', tipo='$tipo', id_empleado=$id_empleado 
          WHERE id=$id";
    if ($conexion->query($query)) {
        header("Location: ../edicion_tickets.php?actualizado=1");
    } else {
        header("Location: ../edicion_tickets.php?error=1");
    }
    exit();
} else {
    header("Location: ../edicion_tickets.php");
    exit();
}

<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/conexion.php';

// Simular sedes para fines de prueba
$sedes = [];
$result = $conexion->query("SELECT * FROM sedes");
while ($row = $result->fetch_assoc()) {
    $sedes[] = $row;
}

// Obtener gastos
$gastos = $conexion->query("SELECT g.descripcion, g.monto, g.fecha, s.nombre AS sede FROM gastos_oficina g JOIN sedes s ON g.id_sede = s.id ORDER BY g.fecha DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos Semanales</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h2 {
            color: #2d3748;
            font-weight: 700;
            margin: 0;
        }
        .btn-add {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
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
            margin-bottom: 20px;
        }
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .table thead {
            background: linear-gradient(135deg, #ef476f 0%, #d62828 100%);
            color: white;
        }
        .table thead th {
            padding: 15px;
            font-weight: 600;
            border: none;
        }
        .table tbody td {
            padding: 15px;
        }
        .table tbody tr:hover {
            background: #fff5f5;
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
        .btn-save {
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
        }
        .btn-cancel {
            background: #6c757d;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
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
            <h2><i class="fas fa-money-bill-wave"></i> Gastos Semanales</h2>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalAgregarGasto">
                <i class="fas fa-plus-circle"></i> Agregar Gasto
            </button>
        </div>

        <a href="informes.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver
        </a>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-align-left"></i> Descripción</th>
                        <th><i class="fas fa-dollar-sign"></i> Monto</th>
                        <th><i class="fas fa-calendar"></i> Fecha</th>
                        <th><i class="fas fa-building"></i> Sede</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($g = $gastos->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($g['descripcion']) ?></td>
                            <td style="color: #ef476f; font-weight: 700;">$<?= number_format($g['monto'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($g['fecha']) ?></td>
                            <td><?= htmlspecialchars($g['sede']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalAgregarGasto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="controllers/agregar_gasto.php">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle"></i> Agregar Gasto
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">
                                <i class="fas fa-align-left"></i> Descripción
                            </label>
                            <input type="text" name="descripcion" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">
                                <i class="fas fa-dollar-sign"></i> Monto
                            </label>
                            <input type="number" step="0.01" name="monto" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">
                                <i class="fas fa-calendar"></i> Fecha
                            </label>
                            <input type="date" name="fecha" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600;">
                                <i class="fas fa-building"></i> Sede
                            </label>
                            <select name="id_sede" class="form-select" required>
                                <option value="">Seleccionar sede</option>
                                <?php foreach ($sedes as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

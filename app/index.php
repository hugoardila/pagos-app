<?php
session_start();

// Redirigir al login si no hay sesión activa
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: views/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Pagos - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        /* Navbar Profesional */
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-brand i {
            font-size: 28px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 15px !important;
            border-radius: 8px;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }
        
        .btn-nav-custom {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white !important;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-nav-custom:hover {
            background: white;
            color: #667eea !important;
            border-color: white;
        }
        
        .btn-admin-custom {
            background: #ffc107;
            border: 2px solid #ffc107;
            color: #000 !important;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-admin-custom:hover {
            background: #ffcd39;
            border-color: #ffcd39;
            transform: translateY(-2px);
        }
        
        /* Contenedor principal */
        .main-container {
            padding: 40px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header del dashboard */
        .dashboard-header {
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInDown 0.6s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dashboard-header h1 {
            font-size: 42px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .dashboard-header p {
            font-size: 18px;
            color: #718096;
        }
        
        /* Cards de módulos mejoradas */
        .module-card {
            background: white;
            border-radius: 20px;
            padding: 35px 25px;
            text-align: center;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out backwards;
        }
        
        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--card-color-1), var(--card-color-2));
        }
        
        .module-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .module-icon {
            font-size: 55px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--card-color-1), var(--card-color-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .module-card h5 {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .module-card p {
            font-size: 14px;
            color: #718096;
            margin-bottom: 25px;
        }
        
        .btn-module {
            background: linear-gradient(135deg, var(--card-color-1), var(--card-color-2));
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-module:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            color: white;
        }
        
        /* Colores por módulo */
        .card-1 { --card-color-1: #667eea; --card-color-2: #764ba2; }
        .card-2 { --card-color-1: #06d6a0; --card-color-2: #118ab2; }
        .card-3 { --card-color-1: #ffd166; --card-color-2: #ef476f; }
        .card-4 { --card-color-1: #06d6a0; --card-color-2: #06d6a0; }
        .card-5 { --card-color-1: #2d3748; --card-color-2: #4a5568; }
        .card-6 { --card-color-1: #8b5cf6; --card-color-2: #ec4899; }
        
        /* Animaciones escalonadas */
        .col:nth-child(1) .module-card { animation-delay: 0.1s; }
        .col:nth-child(2) .module-card { animation-delay: 0.2s; }
        .col:nth-child(3) .module-card { animation-delay: 0.3s; }
        .col:nth-child(4) .module-card { animation-delay: 0.4s; }
        .col:nth-child(5) .module-card { animation-delay: 0.5s; }
        .col:nth-child(6) .module-card { animation-delay: 0.6s; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header h1 {
                font-size: 32px;
            }
            
            .module-card {
                padding: 25px 20px;
            }
            
            .module-icon {
                font-size: 45px;
            }
        }
    </style>
</head>
<body>
    <!-- Barra de navegación mejorada -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#">
                <i class="fas fa-money-check-alt"></i>
                <span>Pagos App</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-2">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user-circle"></i> 
                            Bienvenido, <strong><?php echo $_SESSION['usuario_nombre']; ?></strong>
                        </span>
                    </li>

<?php if (isset($_SESSION['usuario_rol']) && trim(strtolower($_SESSION['usuario_rol'])) == 'admin'): ?>
    <li class='nav-item'>
        <a href="views/usuarios.php" class='btn btn-admin-custom'>
            <i class="fas fa-users-cog"></i> Administrar Usuarios
        </a>
    </li>
<?php endif; ?>

                    <li class="nav-item">
                        <a class="btn btn-nav-custom" href="views/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-th-large"></i> Panel de Control</h1>
            <p>Selecciona un módulo para comenzar</p>
        </div>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <div class="col">
                <div class="card module-card card-1">
                    <div class="module-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h5>Pago de Empleados</h5>
                    <p>Gestiona los pagos y adelantos del personal</p>
                    <a href="views/pago_empleados.php" class="btn-module">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
            
            <div class="col">
                <div class="card module-card card-2">
                    <div class="module-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h5>Registro de Tickets</h5>
                    <p>Registra y administra los tickets de venta</p>
                    <a href="views/registro_tickets.php" class="btn-module">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
            
            <div class="col">
                <div class="card module-card card-3">
                    <div class="module-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h5>Total COP por Sede</h5>
                    <p>Visualiza los totales por ubicación</p>
                    <a href="views/total_cop_sede.php" class="btn-module">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
            
            <div class="col">
                <div class="card module-card card-4">
                    <div class="module-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h5>Historial de Pagos</h5>
                    <p>Consulta el historial completo de pagos</p>
                    <a href="views/historial_pagos.php" class="btn-module">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
            
            <div class="col">
                <div class="card module-card card-5">
                    <div class="module-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5>Informes</h5>
                    <p>Genera reportes y estadísticas detalladas</p>
                    <a href="views/informes.php" class="btn-module">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
            
            <div class="col">
                <div class="card module-card card-6">
                    <div class="module-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5>Empleados</h5>
                    <p>Administra la información de empleados</p>
                    <a href="views/empleados.php" class="btn-module">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>

            <div class="col">
                <div class="card module-card card-1">
                    <div class="module-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h5>Configurar Tasas</h5>
                    <p>Edita las tasas de cambio para los cálculos</p>
                    <a href="views/pago_empleados.php#tasas-cambio" class="btn-module">
                        <i class="fas fa-arrow-right"></i> Acceder
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

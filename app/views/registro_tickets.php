<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container-ticket {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 1250px;
            width: 100%;
            animation: fadeInUp 0.6s ease-out;
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
        
        .ticket-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .ticket-header h2 {
            color: #2d3748;
            font-weight: 700;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .ticket-header p {
            color: #718096;
            margin: 0;
        }
        
        .form-group-custom {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label-custom {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-label-custom i {
            margin-right: 5px;
            color: #667eea;
        }
        
        .form-control-custom {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control-custom:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .form-select-custom {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-select-custom:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }
        
        .ticket-row {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 8px;
            margin-bottom: 8px;
            background: #fafbff;
        }

        .ticket-row-grid {
            display: grid;
            grid-template-columns: 56px 2fr 2.2fr 1fr 1.1fr 1.1fr auto;
            gap: 8px;
            align-items: center;
        }

        .ticket-indice {
            font-size: 12px;
            font-weight: 700;
            color: #667eea;
            text-align: center;
        }

        .empleado-wrap {
            position: relative;
        }
        
        .suggestion-box {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            border: 2px solid #667eea;
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .suggestion-box div {
            padding: 12px 15px;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .suggestion-box div:hover {
            background: #f8f9ff;
            color: #667eea;
        }
        
        .invalid-feedback {
            display: none;
            color: #ef476f;
            font-size: 0.8em;
            margin-top: 4px;
        }
        
        .is-invalid {
            border-color: #ef476f !important;
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #d4edda;
            color: #155724;
            animation: slideInDown 0.5s ease-out;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .buttons-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn-register {
            flex: 1;
            min-width: 180px;
            background: linear-gradient(135deg, #06d6a0 0%, #118ab2 100%);
            border: none;
            color: white;
            padding: 14px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 214, 160, 0.4);
            color: white;
        }
        
        .btn-edit-tickets {
            flex: 1;
            min-width: 150px;
            background: #ffc107;
            border: none;
            color: #000;
            padding: 14px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-edit-tickets:hover {
            background: #ffcd39;
            transform: translateY(-2px);
            color: #000;
        }
        
        .btn-back-custom {
            flex: 1;
            min-width: 150px;
            background: #6c757d;
            border: none;
            color: white;
            padding: 14px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-back-custom:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }
        
        @media (max-width: 768px) {
            .container-ticket {
                padding: 25px;
            }
            
            .ticket-header h2 {
                font-size: 26px;
            }
            
            .buttons-group {
                flex-direction: column;
            }
            
            .btn-register,
            .btn-edit-tickets,
            .btn-back-custom {
                width: 100%;
            }

            .ticket-row-grid {
                grid-template-columns: 1fr;
            }

            .ticket-indice {
                text-align: left;
            }
        }
    </style>

</head>
<body>
    <div class="container-ticket">
        <div class="ticket-header">
            <h2><i class="fas fa-file-invoice"></i> Registrar Ticket</h2>
            <p>Complete los datos del ticket de venta</p>
        </div>

        <?php if (isset($_GET['exito']) && $_GET['exito'] == 1): ?>
            <div class="alert-custom">
                <i class="fas fa-check-circle fa-lg"></i>
                <span>
                    Ticket(s) registrado(s) correctamente.
                    <?php if (isset($_GET['cantidad'])): ?>
                        Total: <strong><?= intval($_GET['cantidad']) ?></strong>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <form id="ticketForm" action="guardar_ticket.php" method="POST" autocomplete="off">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; gap:10px; flex-wrap:wrap;">
                <label class="form-label-custom" style="margin:0;">
                    <i class="fas fa-layer-group"></i> Registro múltiple de tickets
                </label>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="button" class="btn btn-sm btn-primary" id="btnAgregarFila">
                        <i class="fas fa-plus"></i> Agregar fila
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregar20">
                        <i class="fas fa-layer-group"></i> Agregar 20 filas
                    </button>
                </div>
            </div>

            <div id="filasTickets"></div>

            <small style="display:block; margin-top:8px; color:#667eea; font-weight:600;">
                <i class="fas fa-sliders-h"></i>
                ¿Necesitas ajustar tasas? <a href="pago_empleados.php#tasas-cambio">Editar tasas de cambio</a>
            </small>

            <div class="buttons-group">
                <button type="submit" class="btn-register" id="btnRegistrar">
                    <i class="fas fa-save"></i> Registrar Ticket
                </button>
                <a href="edicion_tickets.php" class="btn-edit-tickets">
                    <i class="fas fa-edit"></i> Editar Tickets
                </a>
                <a href="../index.php" class="btn-back-custom">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </form>
    </div>
    <script>
        const filasTickets = document.getElementById('filasTickets');
        const btnAgregarFila = document.getElementById('btnAgregarFila');
        const btnAgregar20 = document.getElementById('btnAgregar20');
        const form = document.getElementById('ticketForm');
        const MAX_FILAS = 200;
        let contadorFilas = 0;

        function crearFila() {
            contadorFilas++;
            const fila = document.createElement('div');
            fila.className = 'form-group-custom ticket-row';
            fila.dataset.fila = contadorFilas;

            fila.innerHTML = `
                <div class="ticket-row-grid">
                    <div class="ticket-indice">#${contadorFilas}</div>

                    <div class="empleado-wrap">
                        <input type="text" class="form-control-custom nombre-empleado" name="nombre_empleado[]" placeholder="Empleado" required>
                        <input type="hidden" name="id_empleado[]" class="id-empleado">
                        <div class="suggestion-box d-none"></div>
                        <div class="invalid-feedback">
                            <i class="fas fa-exclamation-triangle"></i> Selecciona un empleado.
                        </div>
                    </div>

                    <input type="text" class="form-control-custom" name="descripcion[]" placeholder="Descripción" required>

                    <input type="number" class="form-control-custom" name="monto[]" step="0.01" placeholder="Monto" required>

                    <select class="form-select-custom" name="moneda[]" required>
                        <option value="COP">COP</option>
                        <option value="USD">USD</option>
                        <option value="MXN">MXN</option>
                        <option value="QZ">QZ</option>
                    </select>

                    <select class="form-select-custom" name="tipo[]" required>
                        <option value="enganche">Enganche</option>
                        <option value="retaque">Retaque</option>
                    </select>

                    <button type="button" class="btn btn-sm btn-outline-danger btnEliminarFila" title="Eliminar fila">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;

            filasTickets.appendChild(fila);
            actualizarEstadoBotonesEliminar();
        }

        function actualizarEstadoBotonesEliminar() {
            const botones = filasTickets.querySelectorAll('.btnEliminarFila');
            const deshabilitar = botones.length <= 1;
            botones.forEach(btn => {
                btn.disabled = deshabilitar;
            });
        }

        function agregarVariasFilas(cantidad) {
            for (let i = 0; i < cantidad; i++) {
                if (filasTickets.children.length >= MAX_FILAS) break;
                crearFila();
            }
        }

        btnAgregarFila.addEventListener('click', () => agregarVariasFilas(1));
        btnAgregar20.addEventListener('click', () => agregarVariasFilas(20));

        filasTickets.addEventListener('click', (e) => {
            const btnEliminar = e.target.closest('.btnEliminarFila');
            if (!btnEliminar) return;
            const fila = btnEliminar.closest('.form-group-custom');
            if (fila) fila.remove();
            actualizarEstadoBotonesEliminar();
        });

        filasTickets.addEventListener('input', (e) => {
            if (!e.target.classList.contains('nombre-empleado')) return;

            const inputEmpleado = e.target;
            const fila = inputEmpleado.closest('.form-group-custom');
            const sugerencias = fila.querySelector('.suggestion-box');
            const idEmpleado = fila.querySelector('.id-empleado');
            const feedbackEmpleado = fila.querySelector('.invalid-feedback');
            const valor = inputEmpleado.value.trim();

            idEmpleado.value = '';
            inputEmpleado.classList.remove('is-invalid');
            feedbackEmpleado.style.display = 'none';

            if (valor.length < 2) {
                sugerencias.classList.add('d-none');
                sugerencias.innerHTML = '';
                return;
            }

            fetch('buscar_empleados.php?nombre=' + encodeURIComponent(valor))
                .then(response => response.json())
                .then(data => {
                    sugerencias.innerHTML = '';
                    if (!Array.isArray(data) || data.length === 0) {
                        sugerencias.classList.add('d-none');
                        return;
                    }

                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.textContent = item.nombre;
                        div.addEventListener('click', () => {
                            inputEmpleado.value = item.nombre;
                            idEmpleado.value = item.id;
                            sugerencias.classList.add('d-none');
                            inputEmpleado.classList.remove('is-invalid');
                            feedbackEmpleado.style.display = 'none';
                        });
                        sugerencias.appendChild(div);
                    });

                    sugerencias.classList.remove('d-none');
                })
                .catch(() => {
                    sugerencias.classList.add('d-none');
                });
        });

        document.addEventListener('click', (e) => {
            document.querySelectorAll('#filasTickets .suggestion-box').forEach(box => {
                if (!box.contains(e.target) && !box.closest('.form-group-custom').contains(e.target)) {
                    box.classList.add('d-none');
                }
            });
        });

        form.addEventListener('submit', function(e) {
            let hayError = false;
            const filas = filasTickets.querySelectorAll('.form-group-custom');

            if (filas.length === 0) {
                e.preventDefault();
                return;
            }

            filas.forEach(fila => {
                const inputEmpleado = fila.querySelector('.nombre-empleado');
                const idEmpleado = fila.querySelector('.id-empleado');
                const feedbackEmpleado = fila.querySelector('.invalid-feedback');

                if (!idEmpleado.value) {
                    hayError = true;
                    inputEmpleado.classList.add('is-invalid');
                    feedbackEmpleado.style.display = 'block';
                }
            });

            if (hayError) e.preventDefault();
        });

        // Inicial: crear 1 fila para comenzar rápido
        agregarVariasFilas(1);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

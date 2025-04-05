<?php
session_start();
date_default_timezone_set('America/Lima');
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'tecnico') {
    header('Location: login.php');
    exit;
}
$usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario no definido';
$role = $_SESSION['role'];

include '../../conexion.php';
$conn = $conn;

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Consultar herramientas en almacén
$sql_h = "SELECT id_herramientas, nombre_herramientas 
          FROM tbl_herramientas 
          WHERE ubicacion_herramientas = 'En almacen'";
$resultado_h = $conn->query($sql_h);
if (!$resultado_h) {
    die("Error en consulta de herramientas: " . $conn->error);
}

// Consultar activos en almacén
$sql_act = "SELECT id_activos, nombre_activos 
            FROM tbl_activos 
            WHERE ubicacion_activos = 'En almacen'";
$resultado_act = $conn->query($sql_act);
if (!$resultado_act) {
    die("Error en consulta de activos: " . $conn->error);
}

// Consultar consumibles en almacén
$sql_con = "SELECT id_consumibles, nombre_consumibles, cantidad_consumibles 
            FROM tbl_consumibles 
            WHERE ubicacion_consumibles = 'En almacen' AND cantidad_consumibles > 0";
$resultado_con = $conn->query($sql_con);
if (!$resultado_con) {
    die("Error en consulta de consumibles: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $destino = trim($_POST['destino']);
    $id_user = $_SESSION['id_user'];
    $selectedItems = json_decode($_POST['body'], true);
    $totalItems = 0;
    $body = "";
    $fecha = date('Y-m-d H:i:s');

    $regex = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/'; 
    
    if (!preg_match($regex, $titulo) || !preg_match($regex, $destino)) {
        echo "<script>alert('El título y destino solo pueden contener letras, números y espacios'); window.location='reg_salidas.php';</script>";
        exit;
    }

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($selectedItems)) {
        echo "<script>alert('Error en los datos enviados (JSON inválido o no enviado)'); window.location='reg_salidas.php';</script>";
        exit;
    }

    $conn->begin_transaction();

    try {
        // Procesar herramientas
        if (!empty($selectedItems['herramientas'])) {
            $herramientas = array_values($selectedItems['herramientas']);
            $body .= "Herramientas: (" . implode(", ", $herramientas) . "), ";
            $totalItems += count($herramientas);

            foreach ($selectedItems['herramientas'] as $id => $nombre) {
                $check_stmt = $conn->prepare("SELECT ubicacion_herramientas FROM tbl_herramientas WHERE id_herramientas = ?");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $row = $check_result->fetch_assoc();
                $ubicacion_actual = $row['ubicacion_herramientas'];
                $check_stmt->close();

                if ($ubicacion_actual !== 'En almacen') {
                    throw new Exception("Error: La herramienta $nombre no está en almacén (ubicación actual: $ubicacion_actual)");
                }

                $stmt_mov = $conn->prepare("INSERT INTO tbl_movimientos_herramientas (tipo_movimiento, id_herramientas, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                            VALUES ('salida', ?, 1, 'En almacen', 'En campo', ?, ?)");
                $stmt_mov->bind_param("isi", $id, $fecha, $id_user);
                $stmt_mov->execute();

                $update_stmt = $conn->prepare("UPDATE tbl_herramientas SET ubicacion_herramientas = 'En campo' WHERE id_herramientas = ?");
                $update_stmt->bind_param("i", $id);
                $update_stmt->execute();

                $stmt_mov->close();
                $update_stmt->close();
            }
        }

        // Procesar activos
        if (!empty($selectedItems['activos'])) {
            $activos = array_values($selectedItems['activos']);
            $body .= "Activos: (" . implode(", ", $activos) . "), ";
            $totalItems += count($activos);

            foreach ($selectedItems['activos'] as $id => $nombre) {
                $check_stmt = $conn->prepare("SELECT ubicacion_activos FROM tbl_activos WHERE id_activos = ?");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $row = $check_result->fetch_assoc();
                $ubicacion_actual = $row['ubicacion_activos'];
                $check_stmt->close();

                if ($ubicacion_actual !== 'En almacen') {
                    throw new Exception("Error: El activo $nombre no está en almacén (ubicación actual: $ubicacion_actual)");
                }

                $stmt_mov = $conn->prepare("INSERT INTO tbl_movimientos_activos (tipo_movimiento, id_activos, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                            VALUES ('salida', ?, 1, 'En almacen', 'En instalación', ?, ?)");
                $stmt_mov->bind_param("isi", $id, $fecha, $id_user);
                $stmt_mov->execute();

                $update_stmt = $conn->prepare("UPDATE tbl_activos SET ubicacion_activos = 'En instalación' WHERE id_activos = ?");
                $update_stmt->bind_param("i", $id);
                $update_stmt->execute();

                $stmt_mov->close();
                $update_stmt->close();
            }
        }

        // Procesar consumibles
        if (!empty($selectedItems['consumibles'])) {
            $consumibles = [];
            foreach ($selectedItems['consumibles'] as $id => $data) {
                $nombre = $data['nombre'];
                $cantidad = (int)$data['cantidad'];

                $check_stmt = $conn->prepare("SELECT cantidad_consumibles, ubicacion_consumibles FROM tbl_consumibles WHERE id_consumibles = ?");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $row = $check_result->fetch_assoc();
                $cantidad_disponible = (int)$row['cantidad_consumibles'];
                $ubicacion_actual = $row['ubicacion_consumibles'];
                $check_stmt->close();

                if ($ubicacion_actual !== 'En almacen') {
                    throw new Exception("Error: El consumible $nombre no está en almacén (ubicación actual: $ubicacion_actual)");
                }

                if ($cantidad > $cantidad_disponible) {
                    throw new Exception("Error: La cantidad solicitada ($cantidad) para $nombre excede la disponible ($cantidad_disponible)");
                }

                if ($cantidad > 0) {
                    $consumibles[] = "$nombre($cantidad)";
                    $totalItems += $cantidad;

                    // Registrar la salida de la cantidad seleccionada a "En campo"
                    $stmt_mov = $conn->prepare("INSERT INTO tbl_movimientos_consumibles (tipo_movimiento, id_consumibles, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                                VALUES ('salida', ?, ?, 'En almacen', 'En campo', ?, ?)");
                    $stmt_mov->bind_param("iisi", $id, $cantidad, $fecha, $id_user);
                    $stmt_mov->execute();

                    // Actualizar tbl_consumibles: restar la cantidad llevada y mantener "En almacen" si queda algo
                    $new_cantidad = $cantidad_disponible - $cantidad;
                    $new_ubicacion = $new_cantidad > 0 ? 'En almacen' : 'En campo';
                    $update_stmt = $conn->prepare("UPDATE tbl_consumibles 
                                                   SET cantidad_consumibles = ?, 
                                                       ubicacion_consumibles = ? 
                                                   WHERE id_consumibles = ?");
                    $update_stmt->bind_param("isi", $new_cantidad, $new_ubicacion, $id);
                    $update_stmt->execute();

                    $stmt_mov->close();
                    $update_stmt->close();
                }
            }
            if (!empty($consumibles)) {
                $body .= "Consumibles: [" . implode(", ", $consumibles) . "]";
            }
        }

        $stmt = $conn->prepare("INSERT INTO tbl_reg_salidas (titulo, Destino, id_user, body, items) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisi", $titulo, $destino, $id_user, $body, $totalItems);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo "<script>alert('Salida registrada exitosamente'); window.location='reg_salidas.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('" . $e->getMessage() . "'); window.location='reg_salidas.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Salidas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --verde-oscuro: #1C4029;
            --verde-claro: #53A670;
            --beige: #D9D2B0;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: var(--beige);
            background-image: url('../img/fond.jpg'); 
            background-size: cover;
            background-position: center; 
            background-repeat: no-repeat; 
            background-attachment: fixed;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        .text-shadow {
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }
        .custom-input, .custom-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--verde-oscuro);
            border-radius: 8px;
            background-color: white;
            color: #1f2937;
            transition: border-color 0.3s ease;
        }
        .custom-input:focus, .custom-select:focus {
            outline: none;
            border-color: var(--verde-claro);
            box-shadow: 0 0 0 2px rgba(83, 166, 112, 0.2);
        }
        .custom-checkbox {
            accent-color: var(--verde-claro);
        }
        .error-text {
            color: #ef4444;
            font-size: 0.875rem;
        }
        .border-error {
            border-color: #ef4444 !important;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            function validarTexto(input) {
                const regex = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s.,;:¿?¡!()-]+$/;
                return regex.test(input);
            }

            function restringirEntrada(input) {
                input.value = input.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s.,;:¿?¡!()-]/g, '');
            }

            const tituloInput = document.getElementById('titulo');
            const destinoInput = document.getElementById('destino');

            tituloInput.addEventListener('input', function(e) {
                restringirEntrada(this);
                if (!validarTexto(this.value)) {
                    this.classList.add('border-error');
                } else {
                    this.classList.remove('border-error');
                }
            });

            destinoInput.addEventListener('input', function(e) {
                restringirEntrada(this);
                if (!validarTexto(this.value)) {
                    this.classList.add('border-error');
                } else {
                    this.classList.remove('border-error');
                }
            });

            window.validarFormulario = function() {
                const titulo = tituloInput.value;
                const destino = destinoInput.value;
                
                if (!validarTexto(titulo)) {
                    alert('El título solo puede contener letras, números y signos básicos de puntuación (sin @ ni símbolos especiales)');
                    return false;
                }
                
                if (!validarTexto(destino)) {
                    alert('El destino solo puede contener letras, números y signos básicos de puntuación (sin @ ni símbolos especiales)');
                    return false;
                }
                
                return true;
            };

            let selectedItems = {
                herramientas: {},
                activos: {},
                consumibles: {}
            };

            document.querySelectorAll('.herramienta-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    
                    if (this.checked) {
                        selectedItems.herramientas[id] = nombre;
                    } else {
                        delete selectedItems.herramientas[id];
                    }
                    actualizarResumen();
                });
            });

            document.querySelectorAll('.activo-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    
                    if (this.checked) {
                        selectedItems.activos[id] = nombre;
                    } else {
                        delete selectedItems.activos[id];
                    }
                    actualizarResumen();
                });
            });

            window.validarCantidad = function (id) {
                let cantidadElemento = document.getElementById(`cantidad-${id}`);
                let errorElemento = document.getElementById(`error-${id}`);
                
                if (!cantidadElemento || !errorElemento) return;
                
                cantidadElemento.value = cantidadElemento.value.replace(/[^0-9]/g, '');
                let cantidadDisponible = parseInt(cantidadElemento.max) || 0;
                let cantidadIngresada = parseInt(cantidadElemento.value) || 0;

                if (cantidadIngresada < 0) {
                    cantidadIngresada = 0;
                    errorElemento.textContent = "La cantidad no puede ser negativa";
                } else if (cantidadIngresada > cantidadDisponible) {
                    cantidadIngresada = cantidadDisponible;
                    errorElemento.textContent = `No puedes seleccionar más de ${cantidadDisponible}`;
                } else {
                    errorElemento.textContent = "";
                }
                cantidadElemento.value = cantidadIngresada;

                let nombreElemento = cantidadElemento.closest('li')?.querySelector('span');
                let nombre = nombreElemento ? nombreElemento.textContent.split('(')[0].trim() : 'Desconocido';

                if (cantidadIngresada > 0) {
                    selectedItems.consumibles[id] = { nombre, cantidad: cantidadIngresada };
                } else {
                    delete selectedItems.consumibles[id];
                }
                actualizarResumen();
            };

            function actualizarResumen() {
                let resumen = [];
                let totalItems = 0;

                if (Object.keys(selectedItems.herramientas).length > 0) {
                    let herramientas = Object.values(selectedItems.herramientas);
                    resumen.push(`Herramientas: (${herramientas.join(", ")})`);
                    totalItems += herramientas.length;
                }

                if (Object.keys(selectedItems.activos).length > 0) {
                    let activos = Object.values(selectedItems.activos);
                    resumen.push(`Activos: (${activos.join(", ")})`);
                    totalItems += activos.length;
                }

                if (Object.keys(selectedItems.consumibles).length > 0) {
                    let consumibles = Object.values(selectedItems.consumibles).map(item => `${item.nombre}(${item.cantidad})`);
                    resumen.push(`Consumibles: [${consumibles.join(", ")}]`);
                    totalItems += Object.values(selectedItems.consumibles).reduce((sum, item) => sum + item.cantidad, 0);
                }

                document.getElementById('selectedList').innerHTML = resumen.join(", ");
                document.getElementById('bodyField').value = JSON.stringify(selectedItems);
                document.getElementById('totalItems').textContent = totalItems;
            }

            function buscarHerramientas() {
                let input = document.getElementById('searchHerramientas').value.toLowerCase();
                document.querySelectorAll('.herramienta').forEach(herramienta => {
                    let texto = herramienta.textContent.toLowerCase();
                    herramienta.style.display = texto.includes(input) ? 'block' : 'none';
                });
            }

            function buscarActivos() {
                let input = document.getElementById('searchActivos').value.toLowerCase();
                document.querySelectorAll('.activo').forEach(activo => {
                    let texto = activo.textContent.toLowerCase();
                    activo.style.display = texto.includes(input) ? 'block' : 'none';
                });
            }

            function buscarConsumibles() {
                let input = document.getElementById('searchConsumibles').value.toLowerCase();
                document.querySelectorAll('.consumible').forEach(consumible => {
                    let texto = consumible.textContent.toLowerCase();
                    consumible.style.display = texto.includes(input) ? 'block' : 'none';
                });
            }

            document.getElementById("searchHerramientas").addEventListener("input", buscarHerramientas);
            document.getElementById("searchActivos").addEventListener("input", buscarActivos);
            document.getElementById("searchConsumibles").addEventListener("input", buscarConsumibles);

            function actualizarFechaHora() {
                const ahora = new Date();
                const fechaHoraFormateada = ahora.toLocaleString('es-ES', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                document.getElementById("fechaHora").textContent = `Fecha/Hora Ingreso: ${fechaHoraFormateada}`;
            }
            actualizarFechaHora();
            setInterval(actualizarFechaHora, 1000);

            window.abrirModal = function(modalId) {
                document.getElementById(modalId).style.display = 'flex';
            }

            window.cerrarModal = function(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            }
        });
    </script>
</head>
<body class="min-h-screen p-6">
    <?php include '../header.php'; ?>
    <?php include 'sidebartec.php'; ?>

    <div class="main-content">
        <div class="flex justify-between items-center mt-4 px-4 animate-fade-in">
            <p class="text-white text-sm sm:text-lg text-shadow">
                <strong>User:</strong> <?php echo htmlspecialchars($usuario); ?> 
                <span id="user-role"><?php echo !empty($role) ? "($role)" : ''; ?></span>
            </p>
            <p id="fechaHora" class="text-white text-sm sm:text-lg text-shadow">
                <strong>Fecha/Hora Ingreso:</strong> Cargando...
            </p>
        </div>

        <div class="p-6 animate-fade-in">
            <div class="flex justify-around mb-4">
                <button onclick="abrirModal('modalHerramientas')" 
                        class="rounded-lg text-sm px-6 py-3 border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-[var(--verde-oscuro)] transition-all duration-300">
                    Herramientas
                </button>
                <button onclick="abrirModal('modalActivos')" 
                        class="rounded-lg text-sm px-6 py-3 border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-[var(--verde-oscuro)] transition-all duration-300">
                    Activos
                </button>
                <button onclick="abrirModal('modalConsumibles')" 
                        class="rounded-lg text-sm px-6 py-3 border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-[var(--verde-oscuro)] transition-all duration-300">
                    Consumibles
                </button>
            </div>

            <div class="bg-white p-4 rounded-lg shadow-lg mb-6">
                <h3 class="text-lg font-bold text-[var(--verde-oscuro)] text-shadow">Resumen de Selección</h3>
                <p id="selectedList" class="text-[var(--verde-oscuro)] mb-2"></p>
                <p class="text-[var(--verde-oscuro)]"><strong>Total de Items:</strong> <span id="totalItems">0</span></p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg">
                <form method="POST" class="space-y-6" onsubmit="return validarFormulario()">
                    <input type="hidden" id="bodyField" name="body">
                    
                    <div class="relative">
                        <input type="text" id="titulo" name="titulo" required 
                               class="custom-input peer" 
                               placeholder="Título">
                        <label for="titulo" 
                               class="absolute text-[14px] leading-[150%] text-[var(--verde-oscuro)] peer-focus:text-[var(--verde-claro)] duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                            Título
                        </label>
                    </div>
                    
                    <div class="relative">
                        <input type="text" id="destino" name="destino" required 
                               class="custom-input peer" 
                               placeholder="Destino">
                        <label for="destino" 
                               class="absolute text-[14px] leading-[150%] text-[var(--verde-oscuro)] peer-focus:text-[var(--verde-claro)] duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                            Destino
                        </label>
                    </div>

                    <div class="relative">
                        <input type="text" id="id_user" name="id_user" 
                               value="<?php echo htmlspecialchars($usuario); ?>" 
                               class="custom-input peer" 
                               readonly 
                               placeholder="Usuario">
                        <label for="id_user" 
                               class="absolute text-[14px] leading-[150%] text-[var(--verde-oscuro)] peer-focus:text-[var(--verde-claro)] duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                            Usuario
                        </label>
                    </div>

                    <button type="submit" class="w-full rounded-lg text-sm px-6 py-3 border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-[var(--verde-oscuro)] transition-all duration-300">
                        Registrar Salida
                    </button>
                </form>
            </div>
        </div>

        <div id="modalHerramientas" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="cerrarModal('modalHerramientas')">×</span>
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Herramientas (En almacén)</h3>
                <input type="text" id="searchHerramientas" placeholder="Buscar herramienta..." class="custom-input mb-4">
                <ul id="listaHerramientas" class="max-h-64 overflow-y-auto">
                    <?php if ($resultado_h->num_rows == 0): ?>
                        <li class="text-[var(--verde-oscuro)]">No hay herramientas en almacén disponibles.</li>
                    <?php else: ?>
                        <?php while ($fila = $resultado_h->fetch_assoc()): ?>
                        <li class="herramienta mb-2">
                            <label class="flex items-center text-[var(--verde-oscuro)]">
                                <input type="checkbox" 
                                    data-id="<?php echo $fila['id_herramientas']; ?>" 
                                    data-nombre="<?php echo htmlspecialchars($fila['nombre_herramientas'], ENT_QUOTES); ?>" 
                                    class="custom-checkbox mr-2 herramienta-checkbox">
                                <span><?php echo htmlspecialchars($fila['nombre_herramientas']); ?></span>
                            </label>
                        </li>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div id="modalActivos" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="cerrarModal('modalActivos')">×</span>
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Activos (En almacén)</h3>
                <input type="text" id="searchActivos" placeholder="Buscar activo..." class="custom-input mb-4">
                <ul id="listaActivos" class="max-h-64 overflow-y-auto">
                    <?php if ($resultado_act->num_rows == 0): ?>
                        <li class="text-[var(--verde-oscuro)]">No hay activos en almacén disponibles.</li>
                    <?php else: ?>
                        <?php while ($fila = $resultado_act->fetch_assoc()): ?>
                        <li class="activo mb-2">
                            <label class="flex items-center text-[var(--verde-oscuro)]">
                                <input type="checkbox" 
                                    data-id="<?php echo $fila['id_activos']; ?>" 
                                    data-nombre="<?php echo htmlspecialchars($fila['nombre_activos'], ENT_QUOTES); ?>" 
                                    class="custom-checkbox mr-2 activo-checkbox">
                                <span><?php echo htmlspecialchars($fila['nombre_activos']); ?></span>
                            </label>
                        </li>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div id="modalConsumibles" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="cerrarModal('modalConsumibles')">×</span>
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Consumibles (En almacén)</h3>
                <input type="text" id="searchConsumibles" placeholder="Buscar consumible..." class="custom-input mb-4">
                <ul id="listaConsumibles" class="max-h-64 overflow-y-auto">
                    <?php if ($resultado_con->num_rows == 0): ?>
                        <li class="text-[var(--verde-oscuro)]">No hay consumibles en almacén disponibles.</li>
                    <?php else: ?>
                        <?php while ($fila = $resultado_con->fetch_assoc()): ?>
                            <li data-id="<?php echo $fila['id_consumibles']; ?>" data-cantidad="<?php echo $fila['cantidad_consumibles']; ?>" class="consumible flex flex-col p-2 border-b mb-4">
                                <span class="text-[var(--verde-oscuro)]">
                                    <?php echo htmlspecialchars($fila['nombre_consumibles']); ?> 
                                    <strong>(Disponibles: <?php echo $fila['cantidad_consumibles']; ?>)</strong>
                                </span>
                                <div class="flex items-center mt-2">
                                    <input type="number" id="cantidad-<?php echo $fila['id_consumibles']; ?>" 
                                        class="custom-input w-24 mr-2" 
                                        min="0" max="<?php echo $fila['cantidad_consumibles']; ?>" 
                                        value="0" 
                                        oninput="validarCantidad(<?php echo $fila['id_consumibles']; ?>)">
                                </div>
                                <span id="error-<?php echo $fila['id_consumibles']; ?>" class="error-text"></span>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
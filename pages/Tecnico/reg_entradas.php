<?php
session_start();
date_default_timezone_set('America/Lima');
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'tecnico') {
    header('Location: login.php');
    exit;
}
$usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario no definido';
$role = $_SESSION['role'];

include '../../conexion.php'; // $conn ya viene definido desde aquí

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Herramientas: Muestra las que están "En campo"
$sql_h = "SELECT DISTINCT h.id_herramientas, h.nombre_herramientas 
         FROM tbl_herramientas h 
         INNER JOIN tbl_movimientos_herramientas mh ON h.id_herramientas = mh.id_herramientas 
         WHERE mh.tipo_movimiento = 'salida' 
         AND mh.ubicacion_destino = 'En campo' 
         AND NOT EXISTS (
             SELECT 1 FROM tbl_movimientos_herramientas me 
             WHERE me.id_herramientas = mh.id_herramientas 
             AND me.tipo_movimiento = 'entrada' 
             AND me.fecha > mh.fecha
         )";
$resultado_h = $conn->query($sql_h);
if (!$resultado_h) {
    die("Error en consulta de herramientas: " . $conn->error);
}

// Activos: Muestra los que están "En instalación", excluyendo los devueltos a "En almacén"
$sql_act = "SELECT DISTINCT a.id_activos, a.nombre_activos 
            FROM tbl_activos a 
            INNER JOIN tbl_movimientos_activos ma ON a.id_activos = ma.id_activos 
            WHERE ma.tipo_movimiento = 'salida' 
            AND ma.ubicacion_destino = 'En instalación' 
            AND NOT EXISTS (
                SELECT 1 FROM tbl_movimientos_activos me 
                WHERE me.id_activos = ma.id_activos 
                AND me.tipo_movimiento = 'entrada' 
                AND me.ubicacion_destino IN ('Instalado', 'En almacén') 
                AND me.fecha > ma.fecha
            )";
$resultado_act = $conn->query($sql_act);
if (!$resultado_act) {
    die("Error en consulta de activos: " . $conn->error);
}

// Consumibles: Muestra la cantidad "En campo" basada en movimientos
$sql_con = "SELECT c.id_consumibles, c.nombre_consumibles, 
            (SELECT SUM(mc.cantidad) 
             FROM tbl_movimientos_consumibles mc 
             WHERE mc.id_consumibles = c.id_consumibles 
             AND mc.tipo_movimiento = 'salida' 
             AND mc.ubicacion_destino = 'En campo') - 
            IFNULL((SELECT SUM(mc2.cantidad) 
                    FROM tbl_movimientos_consumibles mc2 
                    WHERE mc2.id_consumibles = c.id_consumibles 
                    AND mc2.tipo_movimiento = 'entrada' 
                    AND mc2.ubicacion_destino = 'En almacen'), 0) - 
            IFNULL((SELECT SUM(mc3.cantidad) 
                    FROM tbl_movimientos_consumibles mc3 
                    WHERE mc3.id_consumibles = c.id_consumibles 
                    AND mc3.tipo_movimiento = 'salida' 
                    AND mc3.ubicacion_destino = 'Instalado'), 0) AS cantidad_disponible 
            FROM tbl_consumibles c 
            WHERE EXISTS (
                SELECT 1 FROM tbl_movimientos_consumibles mc 
                WHERE mc.id_consumibles = c.id_consumibles 
                AND mc.tipo_movimiento = 'salida' 
                AND mc.ubicacion_destino = 'En campo'
            )
            HAVING cantidad_disponible > 0";
$resultado_con = $conn->query($sql_con);
if (!$resultado_con) {
    die("Error en consulta de consumibles: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $regex = '/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s.,;:%¿?¡!()-]+$/';
    if (empty($titulo) || !preg_match($regex, $titulo)) {
        echo "<script>alert('El título es obligatorio y solo puede contener letras, números, espacios y signos básicos de puntuación'); window.location='reg_entradas.php';</script>";
        exit;
    }

    $id_user = $_SESSION['id_user'];
    $selectedItems = json_decode($_POST['body'] ?? '', true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($selectedItems)) {
        echo "<script>alert('Error en los datos enviados (JSON inválido o no enviado)'); window.location='reg_entradas.php';</script>";
        exit;
    }

    $totalItems = 0;
    $body = "";
    $fecha_creacion = date('Y-m-d H:i:s');

    $conn->begin_transaction();

    try {
        // Herramientas: Actualizar ubicación a "En almacen"
        if (!empty($selectedItems['herramientas'])) {
            $herramientas = array_map(function ($nombre) {
                return htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
            }, $selectedItems['herramientas']);
            $body .= "Herramientas: (" . implode(", ", $herramientas) . "), ";
            $totalItems += count($selectedItems['herramientas']);

            foreach ($selectedItems['herramientas'] as $id => $nombre) {
                $check_stmt = $conn->prepare("SELECT 1 FROM tbl_movimientos_herramientas mh 
                                              WHERE mh.id_herramientas = ? 
                                              AND mh.tipo_movimiento = 'salida' 
                                              AND mh.ubicacion_destino = 'En campo' 
                                              AND NOT EXISTS (
                                                  SELECT 1 FROM tbl_movimientos_herramientas me 
                                                  WHERE me.id_herramientas = mh.id_herramientas 
                                                  AND me.tipo_movimiento = 'entrada' 
                                                  AND me.fecha > mh.fecha
                                              )");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows == 0) {
                    throw new Exception("Error: La herramienta $nombre no está en campo");
                }
                $check_stmt->close();

                $stmt = $conn->prepare("INSERT INTO tbl_movimientos_herramientas (tipo_movimiento, id_herramientas, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                        VALUES ('entrada', ?, 1, 'En campo', 'En almacen', NOW(), ?)");
                $stmt->bind_param("ii", $id, $id_user);
                if (!$stmt->execute()) {
                    throw new Exception("Error al registrar movimiento de herramienta ID $id: " . $stmt->error);
                }
                $stmt->close();

                $update_stmt = $conn->prepare("UPDATE tbl_herramientas 
                                               SET ubicacion_herramientas = 'En almacen' 
                                               WHERE id_herramientas = ?");
                $update_stmt->bind_param("i", $id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Error al actualizar ubicación de herramienta ID $id: " . $update_stmt->error);
                }
                $update_stmt->close();
            }
        }

        // Activos: Actualizar ubicación según "Sí" o "No"
        if (!empty($selectedItems['activos'])) {
            $activos = [];
            foreach ($selectedItems['activos'] as $id => $data) {
                $nombre = $data['nombre'] ?? '';
                $instalado = $data['instalado'] ?? '';
                if (empty($nombre) || !in_array($instalado, ['si', 'no'])) {
                    throw new Exception("Datos inválidos para el activo ID $id");
                }
                $ubicacion_destino = ($instalado === 'si') ? 'Instalado' : 'En almacén';
                
                $activos[] = "$nombre (" . ($instalado === 'si' ? 'Instalado' : 'En almacén') . ")";
                $totalItems++;

                $check_stmt = $conn->prepare("SELECT 1 FROM tbl_movimientos_activos ma 
                                            WHERE ma.id_activos = ? 
                                            AND ma.tipo_movimiento = 'salida' 
                                            AND ma.ubicacion_destino = 'En instalación' 
                                            AND NOT EXISTS (
                                                SELECT 1 FROM tbl_movimientos_activos me 
                                                WHERE me.id_activos = ma.id_activos 
                                                AND me.tipo_movimiento = 'entrada' 
                                                AND me.ubicacion_destino IN ('Instalado', 'En almacén') 
                                                AND me.fecha > ma.fecha
                                            )");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows == 0) {
                    throw new Exception("Error: El activo $nombre no está en instalación o ya fue procesado");
                }
                $check_stmt->close();

                $stmt = $conn->prepare("INSERT INTO tbl_movimientos_activos (tipo_movimiento, id_activos, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                      VALUES ('entrada', ?, 1, 'En instalación', ?, NOW(), ?)");
                $stmt->bind_param("isi", $id, $ubicacion_destino, $id_user);
                if (!$stmt->execute()) {
                    throw new Exception("Error al registrar movimiento de activo ID $id: " . $stmt->error);
                }
                $stmt->close();

                $update_stmt = $conn->prepare("UPDATE tbl_activos 
                                               SET ubicacion_activos = ? 
                                               WHERE id_activos = ?");
                $update_stmt->bind_param("si", $ubicacion_destino, $id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Error al actualizar ubicación de activo ID $id: " . $update_stmt->error);
                }
                $update_stmt->close();
            }
            $body .= "Activos: (" . implode(", ", $activos) . "), ";
        }

        // Consumibles: Manejar movimientos según ubicaciones
        if (!empty($selectedItems['consumibles'])) {
            $consumibles = [];
            foreach ($selectedItems['consumibles'] as $id => $data) {
                $nombre = $data['nombre'] ?? '';
                $cantidadSeleccionada = (int)($data['cantidad'] ?? 0); // Cantidad devuelta a "En almacén"
                if (empty($nombre)) {
                    throw new Exception("Nombre de consumible inválido para ID $id");
                }

                // Verificar cantidad en campo
                $check_stmt = $conn->prepare("SELECT 
                                                (SELECT SUM(mc.cantidad) 
                                                 FROM tbl_movimientos_consumibles mc 
                                                 WHERE mc.id_consumibles = ? 
                                                 AND mc.tipo_movimiento = 'salida' 
                                                 AND mc.ubicacion_destino = 'En campo') - 
                                                IFNULL((SELECT SUM(mc2.cantidad) 
                                                        FROM tbl_movimientos_consumibles mc2 
                                                        WHERE mc2.id_consumibles = ? 
                                                        AND mc2.tipo_movimiento = 'entrada' 
                                                        AND mc2.ubicacion_destino = 'En almacén'), 0) - 
                                                IFNULL((SELECT SUM(mc3.cantidad) 
                                                        FROM tbl_movimientos_consumibles mc3 
                                                        WHERE mc3.id_consumibles = ? 
                                                        AND mc3.tipo_movimiento = 'salida' 
                                                        AND mc3.ubicacion_destino = 'Instalado'), 0) AS cantidad_disponible,
                                                c.cantidad_consumibles 
                                              FROM tbl_consumibles c 
                                              WHERE c.id_consumibles = ?");
                $check_stmt->bind_param("iiii", $id, $id, $id, $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $row = $check_result->fetch_assoc();
                $cantidad_en_campo = (int)($row['cantidad_disponible'] ?? 0);
                $cantidad_stock_total = (int)($row['cantidad_consumibles'] ?? 0);
                $check_stmt->close();

                if ($cantidad_en_campo <= 0) {
                    throw new Exception("Error: El consumible $nombre no está en campo o ya fue devuelto");
                }
                if ($cantidadSeleccionada > $cantidad_en_campo) {
                    throw new Exception("Error: No puedes devolver más ($cantidadSeleccionada) de $nombre de lo que está en campo ($cantidad_en_campo)");
                }

                // Registrar lo devuelto a "En almacén" y actualizar tbl_consumibles
                if ($cantidadSeleccionada > 0) {
                    $consumibles[] = "$nombre($cantidadSeleccionada)";
                    $totalItems += $cantidadSeleccionada;

                    // Registrar movimiento de entrada
                    $stmt = $conn->prepare("INSERT INTO tbl_movimientos_consumibles (tipo_movimiento, id_consumibles, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                            VALUES ('entrada', ?, ?, 'En campo', 'En almacén', NOW(), ?)");
                    $stmt->bind_param("iii", $id, $cantidadSeleccionada, $id_user);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al registrar movimiento de consumible ID $id: " . $stmt->error);
                    }
                    $stmt->close();

                    // Sumar la cantidad devuelta al stock total en tbl_consumibles
                    $update_stmt = $conn->prepare("UPDATE tbl_consumibles 
                                                   SET cantidad_consumibles = cantidad_consumibles + ? 
                                                   WHERE id_consumibles = ?");
                    $update_stmt->bind_param("ii", $cantidadSeleccionada, $id);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Error al actualizar stock de consumible ID $id: " . $update_stmt->error);
                    }
                    $update_stmt->close();
                }

                // Registrar lo sobrante como "Instalado"
                $cantidad_instalada = $cantidad_en_campo - $cantidadSeleccionada;
                if ($cantidad_instalada > 0) {
                    $stmt = $conn->prepare("INSERT INTO tbl_movimientos_consumibles (tipo_movimiento, id_consumibles, cantidad, ubicacion_origen, ubicacion_destino, fecha, id_user) 
                                            VALUES ('salida', ?, ?, 'En campo', 'Instalado', NOW(), ?)");
                    $stmt->bind_param("iii", $id, $cantidad_instalada, $id_user);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al registrar consumibles instalados ID $id: " . $stmt->error);
                    }
                    $stmt->close();
                }
            }
            if (!empty($consumibles)) {
                $body .= "Consumibles: [" . implode(", ", $consumibles) . "]";
            }
        }

        $stmt = $conn->prepare("INSERT INTO tbl_reg_entradas (fecha_creacion, items, titulo, body, id_user) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $fecha_creacion, $totalItems, $titulo, $body, $id_user);
        if (!$stmt->execute()) {
            throw new Exception("Error al registrar la entrada: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        echo "<script>alert('Entrada registrada exitosamente'); window.location='reg_entradas.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('" . addslashes($e->getMessage()) . "'); window.location='reg_entradas.php';</script>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Entradas</title>
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
            max-width: 600px;
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
            let selectedItems = {
                herramientas: {},
                activos: {},
                consumibles: {}
            };

            window.abrirModal = function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'flex';
                } else {
                    console.error(`Modal con ID ${modalId} no encontrado`);
                }
            };

            window.cerrarModal = function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                } else {
                    console.error(`Modal con ID ${modalId} no encontrado`);
                }
            };

            window.agregarElemento = function (tipo, id, nombre, extra = null) {
                let container = selectedItems[tipo];
                if (tipo === 'activos') {
                    if (extra !== null) {
                        container[id] = { nombre: nombre, instalado: extra };
                    } else {
                        delete container[id];
                    }
                } else if (tipo === 'consumibles' && extra !== null) {
                    if (extra > 0) {
                        container[id] = { nombre: nombre, cantidad: extra };
                    } else {
                        delete container[id];
                    }
                } else {
                    if (container[id]) {
                        delete container[id];
                    } else {
                        container[id] = nombre;
                    }
                }
                actualizarResumen();
            };

            window.validarCantidad = function (id) {
                let cantidadElemento = document.getElementById(`cantidad-${id}`);
                let errorElemento = document.getElementById(`error-${id}`);
                
                if (!cantidadElemento || !errorElemento) return;
                
                cantidadElemento.value = cantidadElemento.value.replace(/[^0-9]/g, '');
                let cantidadIngresada = parseInt(cantidadElemento.value) || 0;
                let cantidadDisponible = parseInt(cantidadElemento.getAttribute('data-max')) || 0;

                if (cantidadIngresada < 0) {
                    cantidadIngresada = 0;
                    errorElemento.textContent = "La cantidad no puede ser negativa";
                } else if (cantidadIngresada > cantidadDisponible) {
                    cantidadIngresada = cantidadDisponible;
                    errorElemento.textContent = `No puedes devolver más de ${cantidadDisponible}`;
                } else {
                    errorElemento.textContent = "";
                }
                cantidadElemento.value = cantidadIngresada;

                let nombreElemento = cantidadElemento.closest('li')?.querySelector('span');
                let nombre = nombreElemento ? nombreElemento.textContent.split('(')[0].trim() : 'Desconocido';
                agregarElemento('consumibles', id, nombre, cantidadIngresada);
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
                    let activos = Object.values(selectedItems.activos).map(item => `${item.nombre} (${item.instalado === 'si' ? 'Instalado' : 'En almacén'})`);
                    resumen.push(`Activos: (${activos.join(", ")})`);
                    totalItems += Object.keys(selectedItems.activos).length;
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

            document.querySelectorAll('.herramienta-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const id = this.getAttribute('data-id');
                    const nombre = this.getAttribute('data-nombre');
                    agregarElemento('herramientas', id, nombre);
                });
            });

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

            document.getElementById("searchHerramientas")?.addEventListener("input", buscarHerramientas);
            document.getElementById("searchActivos")?.addEventListener("input", buscarActivos);

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
                document.getElementById("fechaHora").textContent = `Fecha/Hora: ${fechaHoraFormateada}`;
            }
            actualizarFechaHora();
            setInterval(actualizarFechaHora, 1000);

            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            };
        });
    </script>
</head>
<body class="min-h-screen p-6">
    <?php include '../header.php'; ?>
    <?php include 'sidebartec.php'; ?>

    <div class="main-content">
        <div class="flex justify-between items-center mt-4 px-4 animate-fade-in">
            <p class="text-white text-sm sm:text-lg text-shadow">
                <strong>Usuario:</strong> <?php echo htmlspecialchars($usuario); ?> 
                <span id="user-role"><?php echo !empty($role) ? "($role)" : ''; ?></span>
            </p>
            <p id="fechaHora" class="text-white text-sm sm:text-lg text-shadow">
                <strong>Fecha/Hora:</strong> Cargando...
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
                <form method="POST" class="space-y-6">
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
                        Registrar Entrada
                    </button>
                </form>
            </div>
        </div>

        <div id="modalHerramientas" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="cerrarModal('modalHerramientas')">×</span>
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Herramientas (En campo)</h3>
                <input type="text" id="searchHerramientas" placeholder="Buscar herramienta..." class="custom-input mb-4">
                <ul id="listaHerramientas" class="max-h-64 overflow-y-auto">
                    <?php if ($resultado_h->num_rows == 0): ?>
                        <li class="text-[var(--verde-oscuro)]">No hay herramientas en campo disponibles.</li>
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
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Activos (En instalación)</h3>
                <input type="text" id="searchActivos" placeholder="Buscar activo..." class="custom-input mb-4">
                <ul id="listaActivos" class="max-h-64 overflow-y-auto">
                    <?php if ($resultado_act->num_rows == 0): ?>
                        <li class="text-[var(--verde-oscuro)]">No hay activos en instalación disponibles.</li>
                    <?php else: ?>
                        <?php while ($fila = $resultado_act->fetch_assoc()): ?>
                            <li class="activo mb-2 flex items-center justify-between">
                                <span class="text-[var(--verde-oscuro)]"><?php echo htmlspecialchars($fila['nombre_activos']); ?></span>
                                <div>
                                    <span class="mr-2">¿Se instaló?</span>
                                    <label class="mr-2">
                                        <input type="radio" 
                                            name="activo_<?php echo $fila['id_activos']; ?>" 
                                            onchange="agregarElemento('activos', <?php echo $fila['id_activos']; ?>, '<?php echo addslashes($fila['nombre_activos']); ?>', 'si')"
                                            class="custom-checkbox"> Sí
                                    </label>
                                    <label>
                                        <input type="radio" 
                                            name="activo_<?php echo $fila['id_activos']; ?>" 
                                            onchange="agregarElemento('activos', <?php echo $fila['id_activos']; ?>, '<?php echo addslashes($fila['nombre_activos']); ?>', 'no')"
                                            class="custom-checkbox"> No
                                    </label>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div id="modalConsumibles" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="cerrarModal('modalConsumibles')">×</span>
                <h3 class="text-lg font-bold mb-4 text-[var(--verde-oscuro)] text-shadow">Consumibles (En campo)</h3>
                <ul id="listaConsumibles" class="max-h-64 overflow-y-auto">
                    <?php if ($resultado_con->num_rows == 0): ?>
                        <li class="text-[var(--verde-oscuro)]">No hay consumibles en campo disponibles.</li>
                    <?php else: ?>
                        <?php while ($fila = $resultado_con->fetch_assoc()): ?>
                            <li data-id="<?php echo $fila['id_consumibles']; ?>" class="flex flex-col p-2 border-b mb-4">
                                <span class="text-[var(--verde-oscuro)]">
                                    <?php echo htmlspecialchars($fila['nombre_consumibles']); ?> 
                                    <strong>(En campo: <?php echo $fila['cantidad_disponible']; ?>)</strong>
                                </span>
                                <div class="flex items-center mt-2">
                                    <input type="number" 
                                        id="cantidad-<?php echo $fila['id_consumibles']; ?>" 
                                        class="custom-input w-24 mr-2" 
                                        min="0" 
                                        data-max="<?php echo $fila['cantidad_disponible']; ?>" 
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
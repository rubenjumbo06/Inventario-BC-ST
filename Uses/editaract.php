<?php
require_once("../conexion.php");
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

try {
    // Verificar la conexión
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    if (isset($_GET['id_activos']) && is_numeric($_GET['id_activos'])) {
        $id_activos = intval($_GET['id_activos']);
        $sql = "SELECT * FROM tbl_activos WHERE id_activos = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conn->error);
        }
        $stmt->bind_param("i", $id_activos);
        $stmt->execute();
        $result = $stmt->get_result();
        $activo = $result->fetch_assoc();

        if (!$activo) {
            throw new Exception("Activo no encontrado.");
        }

        // Pasar los valores a la vista
        $id_empresa_selected = $activo['id_empresa'];
        $estado_activos_selected = $activo['estado_activos'];
        $ubicacion_activos_selected = $activo['ubicacion_activos'];
    } else {
        throw new Exception("ID inválido.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obtener los valores del formulario
        $nombre_activos = $_POST['nombre_activos'] ?? null;
        $cantidad_activos = $_POST['cantidad_activos'] ?? null;
        $estado_activos = $_POST['estado_activos'] ?? null;
        $id_empresa = $_POST['id_empresa'] ?? null;
        $IP = $_POST['IP'] ?? null;
        $MAC = $_POST['MAC'] ?? null;
        $SN = $_POST['SN'] ?? null;
        $ubicacion_activos = $_POST['ubicacion_activos'] ?? null;

        // Validación de longitud de campos
        if (strlen($MAC) > 20) {
            throw new Exception("La dirección MAC no puede exceder los 20 caracteres.");
        }
        if (strlen($IP) > 20) {
            throw new Exception("La dirección IP no puede exceder los 20 caracteres.");
        }
        if (strlen($SN) > 30) {
            throw new Exception("El número de serie (SN) no puede exceder los 30 caracteres.");
        }

        // Construir la consulta SQL dinámicamente
        $sql = "UPDATE tbl_activos SET ";
        $params = [];
        $types = "";

        if (!empty($nombre_activos)) {
            $sql .= "nombre_activos=?, ";
            $params[] = $nombre_activos;
            $types .= "s";
        }
        if (!empty($cantidad_activos)) {
            $sql .= "cantidad_activos=?, ";
            $params[] = $cantidad_activos;
            $types .= "i";
        }
        if (!empty($estado_activos)) {
            $sql .= "estado_activos=?, ";
            $params[] = $estado_activos;
            $types .= "s";
        }
        if (!empty($id_empresa)) {
            $sql .= "id_empresa=?, ";
            $params[] = $id_empresa;
            $types .= "i";
        }
        if (!empty($IP)) {
            $sql .= "IP=?, ";
            $params[] = $IP;
            $types .= "s";
        }
        if (!empty($MAC)) {
            $sql .= "MAC=?, ";
            $params[] = $MAC;
            $types .= "s";
        }
        if (!empty($SN)) {
            $sql .= "SN=?, ";
            $params[] = $SN;
            $types .= "s";
        }
        if (!empty($ubicacion_activos)) {
            $sql .= "ubicacion_activos=?, ";
            $params[] = $ubicacion_activos;
            $types .= "s";
        }

        $sql = rtrim($sql, ", ");

        $sql .= " WHERE id_activos=?";
        $params[] = $id_activos;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conn->error);
        }
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            if ($_SESSION['role'] == 'admin') {
                echo "<script>window.location.href='../pages/Admin/activos.php';</script>";
            } else {
                echo "<script>window.location.href='../pages/Usuario/activos.php';</script>";
            }
        } else {
            throw new Exception("Error al actualizar el activo: " . $stmt->error);
        }
    }
} catch (Exception $e) {
    echo "<script>alert('" . addslashes($e->getMessage()) . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Activo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
    <script>
        function limitarLongitud(input, maxLength) {
            if (input.value.length > maxLength) {
                alert(`No puedes escribir más de ${maxLength} caracteres`);
                input.value = input.value.substring(0, maxLength);
                return false;
            }
            return true;
        }
        // Función mejorada de autoajuste
        function autoResize(textarea) {
            // Reset height to calculate new height properly
            textarea.style.height = 'auto';
            // Set new height (with minimum of 1 line)
            textarea.style.height = Math.max(textarea.scrollHeight, 50) + 'px'; // 50px es la altura mínima
        }

        // Inicialización al cargar la página
        document.addEventListener("DOMContentLoaded", function() {
            const textarea = document.getElementById('nombre_activos');
            if (textarea) {
                // Ajuste inicial
                autoResize(textarea);
                
                // Disparar evento input si hay contenido para ajustar el label flotante
                if (textarea.value) {
                    textarea.dispatchEvent(new Event('input'));
                }
            }
        });
    </script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
    <form method="POST" onsubmit="return validarLongitudes()">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <textarea id="nombre_activos" name="nombre_activos"
                        class="block w-full text-sm px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto resize-none"
                        placeholder="Nombre" required
                        oninput="autoResize(this)"><?php echo isset($activo['nombre_activos']) ? htmlspecialchars($activo['nombre_activos']) : ''; ?></textarea>
                    <label for="nombre_activos"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>

                <!-- Cantidad -->
                <div id="input" class="relative">
                    <input type="number" id="cantidad_activos" name="cantidad_activos" value="<?= htmlspecialchars($activo['cantidad_activos']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Cantidad"/>
                    <label for="cantidad"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Cantidad
                    </label>
                </div>

                <!-- Estado -->
                <div id="input" class="relative">
                    <select name="estado_activos" id="estado_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
                        <option value="" disabled>Selecciona un Estado</option>
                        <!-- Opciones de estado cargadas dinámicamente -->
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Estado
                    </label>
                </div>

                <!-- Empresa -->
                <div id="input" class="relative">
                    <select name="id_empresa" id="empresa_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
                        <option value="" disabled>Selecciona una Empresa</option>
                        <!-- Opciones de empresa cargadas dinámicamente -->
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Empresa
                    </label>
                </div>

                <!-- IP -->
                <div id="input" class="relative">
                    <input type="text" id="IP" name="IP" value="<?= htmlspecialchars($activo['IP']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="IP" maxlength="20"
                        oninput="return limitarLongitud(this, 20)"/>
                    <label for="IP"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        IP (max 20 caracteres)
                    </label>
                </div>

                <!-- MAC -->
                <div id="input" class="relative">
                    <input type="text" id="MAC" name="MAC" value="<?= htmlspecialchars($activo['MAC']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="MAC" maxlength="20"
                        oninput="return limitarLongitud(this, 20)"/>
                    <label for="MAC"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        MAC (max 20 caracteres)
                    </label>
                </div>

                <!-- SN -->
                <div id="input" class="relative">
                    <input type="text" id="SN" name="SN" value="<?= htmlspecialchars($activo['SN']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="SN" maxlength="30"
                        oninput="return limitarLongitud(this, 30)"/>
                    <label for="SN"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        SN (max 30 caracteres)
                    </label>
                </div>

                <!-- Ubicacion -->
                <div id="input" class="relative">
                    <select id="ubicacion_select" name="ubicacion_activos"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
                        <option value="" disabled>Selecciona una Ubicación</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Ubicación
                    </label>
                </div>
            </div>
            <div class="sm:flex sm:flex-row-reverse flex gap-4">
                <!-- Botón Guardar -->
                <button type="submit"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-green-900 transition-all duration-300">
                    <div class="flex gap-2 items-center">Actualizar</div>
                </button>
                <!-- Botón Cancelar -->
                <button type="reset"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300"
                    onclick="window.history.back();">
                    <div class="flex gap-2 items-center">Cancelar</div>
                </button>
            </div>
        </form>
    </div>

    <script>
        // Función para limitar la longitud de los campos

        function limitarLongitud(input, maxLength) {
                    if (input.value.length > maxLength) {
                        alert(`No puedes escribir más de ${maxLength} caracteres en este campo`);
                        input.value = input.value.substring(0, maxLength);
                        return false;
                    }
                    return true;
        }

        // Función para validar longitudes antes de enviar el formulario
        function validarLongitudes() {
            const mac = document.getElementById('MAC');
            const ip = document.getElementById('IP');
            const sn = document.getElementById('SN');
            
            if (mac.value.length > 20) {
                alert('La dirección MAC no puede exceder los 20 caracteres');
                return false;
            }
            
            if (ip.value.length > 20) {
                alert('La dirección IP no puede exceder los 20 caracteres');
                return false;
            }
            
            if (sn.value.length > 30) {
                alert('El número de serie (SN) no puede exceder los 30 caracteres');
                return false;
            }
            
            return true;
        }

        document.addEventListener("DOMContentLoaded", function () {
        async function cargarDatos(endpoint, selectId, selectedValue) {
            try {
                let response = await fetch(endpoint);
                if (!response.ok) {
                    throw new Error(`Error en la solicitud: ${response.statusText}`);
                }
                let data = await response.json();
                if (!Array.isArray(data)) {
                    throw new Error("Respuesta no válida");
                }

                let select = document.getElementById(selectId);
                let placeholderText = "";
                switch (selectId) {
                    case "empresa_select":
                        placeholderText = "Selecciona una Empresa";
                        break;
                    case "estado_select":
                        placeholderText = "Selecciona un Estado";
                        break;
                    case "ubicacion_select":
                        placeholderText = "Selecciona una Ubicación";
                        break;
                    default:
                        placeholderText = "Selecciona una opción";
                }
                // Limpiar y agregar el texto predeterminado
                select.innerHTML = `<option value="" disabled selected>${placeholderText}</option>`;

                data.forEach(item => {
                    let option = document.createElement("option");
                    option.value = item.id_empresa || item.id_estado || item; // Para ubicación, item es el valor del ENUM
                    option.textContent = item.nombre || item.nombre_estado || item; // Para ubicación, item es el valor del ENUM
                    if (option.value == selectedValue) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            } catch (error) {
                console.error("Error cargando los datos:", error);
                alert("Error cargando los datos: " + error.message);
            }
        }

        // Obtener los valores seleccionados desde PHP
        let id_empresa_selected = "<?= $id_empresa_selected ?>";
        let estado_activos_selected = "<?= $estado_activos_selected ?>";
        let ubicacion_activos_selected = "<?= $ubicacion_activos_selected ?>";

        // Cargar los datos y seleccionar el valor correcto
        cargarDatos("get_empresas.php", "empresa_select", id_empresa_selected);
        cargarDatos("get_estados.php", "estado_select", estado_activos_selected);
        cargarDatos("get_ubicacion.php", "ubicacion_select", ubicacion_activos_selected);
    });
    </script>
</body>
</html>
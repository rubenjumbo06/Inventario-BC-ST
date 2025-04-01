<?php
include '../conexion.php';
session_start();

// Iniciar buffer de salida
ob_start();

// Inicializar variables del formulario
$nombre_herramientas = $cantidad_herramientas = $id_empresa = $estado_herramientas = $utilidad_herramientas = $ubicacion_herramientas = "";
$mensaje = "";

try {
    // Verificar la conexión a la base de datos
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Procesar el formulario cuando se envíe
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validar nombre (solo letras y espacios)
        $nombre_herramientas = isset($_POST['nombre_herramientas']) ? trim($_POST['nombre_herramientas']) : '';
        if (empty($nombre_herramientas)) {
            throw new Exception("El nombre es requerido");
        }
        
        if (!preg_match('/^[A-Za-zÁ-Úá-úñÑ\s]+$/', $nombre_herramientas)) {
            throw new Exception("El nombre solo puede contener letras y espacios");
        }

        // Sanitizar y validar otros campos
        $nombre_herramientas = htmlspecialchars($nombre_herramientas);
        $cantidad_herramientas = intval($_POST['cantidad_herramientas']);
        $id_empresa = intval($_POST['id_empresa']);
        $estado_herramientas = intval($_POST['estado_herramientas']);
        $utilidad_herramientas = intval($_POST['utilidad_herramientas']);
        $ubicacion_herramientas = htmlspecialchars($_POST['ubicacion_herramientas']);

        // Validar campos obligatorios
        if ($cantidad_herramientas <= 0 || empty($id_empresa) || empty($estado_herramientas) || 
            empty($utilidad_herramientas) || empty($ubicacion_herramientas)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        // Preparar consulta SQL
        $sql = "INSERT INTO tbl_herramientas (nombre_herramientas, cantidad_herramientas, id_empresa, estado_herramientas, utilidad_herramientas, ubicacion_herramientas) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt->bind_param("siiiss", $nombre_herramientas, $cantidad_herramientas, $id_empresa, $estado_herramientas, $utilidad_herramientas, $ubicacion_herramientas);
        
        if ($stmt->execute()) {
            // Redireccionar según el rol
            if ($_SESSION['role'] == 'admin') {
                header("Location: ../pages/Admin/herramientas.php");
            } else {
                header("Location: ../pages/Usuario/herramientas.php");
            }
            ob_end_clean();
            exit();
        } else {
            throw new Exception("Error al guardar los datos: " . $stmt->error);
        }
    }
} catch (Exception $e) {
    $mensaje = $e->getMessage();
    $_SESSION['error'] = $mensaje;
    // Limpiar buffer y mantener en la misma página para mostrar el error
    ob_end_clean();
}

// Limpiar buffer antes de mostrar el HTML
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Herramienta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-col">
                <strong>
                    <div class="text-base text-[var(--verde-oscuro)]">Agregar Herramienta</div>
                </strong>
                <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                    Complete todos los campos requeridos
                </div>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_herramientas" name="nombre_herramientas"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre" 
                        value="<?php echo htmlspecialchars($nombre_herramientas); ?>" 
                        required
                        pattern="[A-Za-zÁ-Úá-úñÑ\s]+"
                        title="Solo se permiten letras y espacios"
                        oninput="validarSoloLetras(this)"
                        onkeydown="return validarTeclaLetras(event)">
                    <label for="nombre_herramientas"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>

                <!-- Cantidad -->
                <div class="relative">
                    <input type="number" id="cantidad_herramientas" name="cantidad_herramientas"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        min="1" max="9999" step="1"
                        placeholder="Cantidad" 
                        value="<?php echo htmlspecialchars($cantidad_herramientas); ?>" 
                        required 
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                        oninput="validarCantidad(this)">
                    <label for="cantidad_herramientas"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Cantidad
                    </label>
                </div>

                <!-- Empresa -->
                <div id="input" class="relative">
                    <select name="id_empresa" id="empresa_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]" required>
                        <option value="" disabled selected>Selecciona una Empresa</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Empresa
                    </label>
                </div>

                
                <!-- Estado -->
                <div id="input" class="relative">
                    <select name="estado_consumibles" id="estado_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]" required>
                        <option value="" disabled selected>Selecciona un Estado</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Estado
                    </label>
                </div>

                <!-- Utilidad -->
                <div id="input" class="relative">
                    <select name="utilidad_consumibles" id="utilidad_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]" required>
                        <option value="" disabled selected>Selecciona un Utilidad</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Utilidad
                    </label>
                </div>

                <!-- Usuario -->
                <div id="input" class="relative">
                    <select name="id_user" id="users_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
                        <option value="" disabled selected>Selecciona un Usuario</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Usuario
                    </label>
                </div>

            </div>

            <div class="sm:flex sm:flex-row-reverse flex gap-4">
                <button type="submit"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] bg-[var(--verde-claro)] text-white font-semibold shadow-md hover:bg-green-900 transition-all duration-300">
                    <div class="flex gap-2 items-center">Guardar</div>
                </button>
                <button type="reset"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300"
                    onclick="window.history.back();">
                    <div class="flex gap-2 items-center">Cancelar</div>
                </button>
            </div>
        </form>
    </div>

    <script>
    // Validación para solo letras (incluye acentos y ñ)
    function validarSoloLetras(input) {
        // Elimina caracteres no permitidos
        input.value = input.value.replace(/[^A-Za-zÁ-Úá-úñÑ\s]/g, '');
        
        // Opcional: convertir primera letra de cada palabra a mayúscula
        input.value = input.value.toLowerCase().replace(/\b\w/g, function(letra) {
            return letra.toUpperCase();
        });
    }

    // Validación al presionar teclas
    function validarTeclaLetras(event) {
        const tecla = event.key;
        const permitidas = /^[A-Za-zÁ-Úá-úñÑ\s]$/;
        
        // Permitir teclas de control
        if (event.ctrlKey || event.altKey || event.metaKey) return true;
        if ([8, 9, 13, 16, 17, 18, 19, 20, 27, 33, 34, 35, 36, 37, 38, 39, 40, 45, 46].includes(event.keyCode)) return true;
        
        // Rechazar si no es una letra permitida
        if (!permitidas.test(tecla)) {
            event.preventDefault();
            return false;
        }
        return true;
    }

    // Validación del campo cantidad
    function validarCantidad(input) {
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value > 9999) input.value = 9999;
        if (input.value < 1) input.value = 1;
    }

    // Validación del formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const nombre = document.getElementById('nombre_herramientas');
        if (!/^[A-Za-zÁ-Úá-úñÑ\s]+$/.test(nombre.value)) {
            alert('El nombre solo puede contener letras y espacios');
            nombre.focus();
            e.preventDefault();
            return;
        }
        
        const cantidad = document.getElementById('cantidad_herramientas');
        if (cantidad.value <= 0 || cantidad.value > 9999 || isNaN(cantidad.value)) {
            alert('La cantidad debe ser un número entre 1 y 9999');
            cantidad.focus();
            e.preventDefault();
        }
    });

    // Cargar datos para selects 
    document.addEventListener("DOMContentLoaded", function() {
        async function cargarDatos(endpoint, selectId) {
            try {
                const response = await fetch(endpoint);
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                const data = await response.json();
                
                if (!Array.isArray(data)) {
                    throw new Error("Respuesta no válida del servidor");
                }

                const select = document.getElementById(selectId);
                let placeholderText = "";
                
                switch (selectId) {
                    case "empresa_select": placeholderText = "Selecciona una Empresa"; break;
                    case "estado_select": placeholderText = "Selecciona un Estado"; break;
                    case "utilidad_select": placeholderText = "Selecciona una Utilidad"; break;
                    case "users_select": placeholderText = "Selecciona un Usuario"; break;
                    default: placeholderText = "Selecciona una opción";
                }

                select.innerHTML = `<option value="" disabled selected>${placeholderText}</option>`;

                data.forEach(item => {
                    const option = document.createElement("option");
                    option.value = item.id_empresa || item.id_estado || item.id_utilidad || item.id_user;
                    option.textContent = item.nombre || item.nombre_estado || item.nombre_utilidad || item.username;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error("Error cargando los datos:", error);
                // Opcional: Mostrar mensaje de error al usuario
            }
        }

        // Cargar datos iniciales
        Promise.all([
            cargarDatos("get_empresas.php", "empresa_select"),
            cargarDatos("get_estados.php", "estado_select"),
            cargarDatos("get_utilidades.php", "utilidad_select"),
            cargarDatos("get_users.php", "users_select")
        ]).catch(error => {
            console.error("Error al cargar datos iniciales:", error);
        });
    });
    </script>
</body>
</html>
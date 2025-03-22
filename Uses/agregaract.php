<?php
include '../conexion.php';
session_start(); 
// Inicializar variables del formulario
$nombre_activos = $cantidad_activos = $estado_activos = $id_empresa = $IP = $MAC = $SN = $ubicacion_activos = "";
$mensaje = "";

// Procesar el formulario cuando se envíe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario y sanitizar
    $nombre_activos = htmlspecialchars($_POST['nombre_activos']);
    $cantidad_activos = htmlspecialchars($_POST['cantidad_activos']);
    $estado_activos = intval($_POST['estado_activos']);
    $id_empresa = intval($_POST['id_empresa']);
    $IP = htmlspecialchars($_POST['IP']);
    $MAC = htmlspecialchars($_POST['MAC']);
    $SN = htmlspecialchars($_POST['SN']);
    $ubicacion_activos = htmlspecialchars($_POST['ubicacion_activos']);

    // Validar longitud de los campos
    if (strlen($MAC) > 20) {
        echo "<script>alert('El campo MAC no puede tener más de 20 caracteres.');</script>";
        exit;
    }
    if (strlen($IP) > 20) {
        echo "<script>alert('El campo IP no puede tener más de 20 caracteres.');</script>";
        exit;
    }
    if (strlen($SN) > 30) {
        echo "<script>alert('El campo SN no puede tener más de 30 caracteres.');</script>";
        exit;
    }

    // Continuar con la inserción si las validaciones pasan
    if (!empty($nombre_activos) && !empty($cantidad_activos) && !empty($estado_activos) && !empty($id_empresa) && !empty($ubicacion_activos)) {
        $sql = "INSERT INTO tbl_activos (nombre_activos, cantidad_activos, estado_activos, id_empresa, IP, MAC, SN, ubicacion_activos) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("siisssss", $nombre_activos, $cantidad_activos, $estado_activos, $id_empresa, $IP, $MAC, $SN, $ubicacion_activos);
            
            if ($stmt->execute()) {
                // Verificar el rol del usuario
                if ($_SESSION['role'] == 'admin') {
                    header("Location: ../pages/Admin/activos.php");
                } else {
                    header("Location: ../pages/Usuario/activos.php");
                }
                exit(); // Asegúrate de salir del script después de la redirección
            } else {
                echo "Error al actualizar la herramienta: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $mensaje = "Error al preparar la consulta: " . $conn->error;
        }
    } else {
        $mensaje = "Todos los campos obligatorios deben estar completos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Datos</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]">Agregar Datos</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Editando tabla: Activos
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mb-10 text-green-500"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form action="agregaract.php" method="POST">

            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_activos" name="nombre_activos"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre" value="<?php echo $nombre_activos; ?>" required />
                    <label for="nombre"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>

                <!-- Cantidad -->
                <div id="input" class="relative">
                    <input type="number" id="cantidad_activos" name="cantidad_activos"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Cantidad" value="<?php echo $cantidad_activos; ?>" required />
                    <label for="cantidad"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Cantidad
                    </label>
                </div>

                <!-- Estado -->
                <div id="input" class="relative">
                    <select name="estado_activos" id="estado_select" class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]" required>
                        <option value="" disabled selected>Selecciona un Estado</option>
                    </select>
                    <label
                        for="floating_outlined"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Estado
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

                <!-- IP -->
                <div id="input" class="relative">
                    <input type="text" id="IP" name="IP" maxlength="20"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="IP" value="<?php echo $IP; ?>" />
                    <label for="IP"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        IP
                    </label>
                </div>

                <!-- MAC -->
                <div id="input" class="relative">
                    <input type="text" id="MAC" name="MAC" maxlength="20"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="MAC" value="<?php echo $MAC; ?>" />
                    <label for="MAC"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        MAC
                    </label>
                </div>

                <!-- SN -->
                <div id="input" class="relative">
                    <input type="text" id="SN" name="SN" maxlength="30"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="SN" value="<?php echo $SN; ?>" />
                    <label for="SN"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        SN
                    </label>
                </div>

                <!-- Ubicacion -->
                <div id="input" class="relative">
                    <select id="floating_outlined" name="ubicacion_activos"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
                        <option value="" disabled selected>Selecciona una Ubicación</option>
                        <option value="1">En Campo</option>
                        <option value="2">En Almacén</option>
                        <option value="3">En Oficina</option>
                        <option value="4">En Instalación</option>
                        <option value="5">Instalado</option>
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
                    <div class="flex gap-2 items-center">Guardar</div>
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
        document.addEventListener("DOMContentLoaded", function () {
        async function cargarDatos(endpoint, selectId) {
            try {
                let response = await fetch(endpoint);
                if (!response.ok) {
                    throw new Error(`Error en la solicitud: ${response.statusText}`);
                }
                let data = await response.json();

                // Verifica si la respuesta es un array
                if (!Array.isArray(data)) {
                    throw new Error("La respuesta no es un array válido. Respuesta: " + JSON.stringify(data));
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
                    default:
                        placeholderText = "Selecciona una opción";
                }
                // Limpiar y agregar el texto predeterminado
                select.innerHTML = `<option value="" disabled selected>${placeholderText}</option>`;

                data.forEach(item => {
                    let option = document.createElement("option");
                    option.value = item.id_empresa || item.id_estado;
                    option.textContent = item.nombre || item.nombre_estado;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error("Error cargando los datos:", error);
                alert("Error cargando los datos: " + error.message);
            }
        }

        // Cargar los datos de empresas y estados
        cargarDatos("get_empresas.php", "empresa_select");
        cargarDatos("get_estados.php", "estado_select");
    });
    </script>
</body>
</html>
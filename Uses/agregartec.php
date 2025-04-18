<?php
include '../conexion.php';
session_start();

// Iniciar buffer de salida
ob_start();

// Inicializar variables del formulario
$nombre_tecnico = $dni_tecnico = $edad_tecnico = $num_telef = "";

try {
    // Verificar la conexión a la base de datos
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Procesar el formulario cuando se envíe
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validar y sanitizar datos
        $nombre_tecnico = isset($_POST['nombre_tecnico']) ? trim(htmlspecialchars($_POST['nombre_tecnico'])) : '';
        $dni_tecnico = isset($_POST['dni_tecnico']) ? trim(htmlspecialchars($_POST['dni_tecnico'])) : '';
        $edad_tecnico = isset($_POST['edad_tecnico']) ? trim(htmlspecialchars($_POST['edad_tecnico'])) : '';
        $num_telef = isset($_POST['num_telef']) ? trim(htmlspecialchars($_POST['num_telef'])) : '';

        // Validar campos obligatorios
        if (empty($nombre_tecnico)) {
            throw new Exception("El nombre del técnico es requerido");
        }
        
        if (empty($dni_tecnico)) {
            throw new Exception("El DNI es requerido");
        }
        
        if (empty($edad_tecnico)) {
            throw new Exception("La edad es requerida");
        }
        
        if (empty($num_telef)) {
            throw new Exception("El número de teléfono es requerido");
        }

        // Preparar consulta SQL
        $sql = "INSERT INTO tbl_tecnico (nombre_tecnico, dni_tecnico, edad_tecnico, num_telef) VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $nombre_tecnico, $dni_tecnico, $edad_tecnico, $num_telef);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Técnico agregado correctamente';
            // Limpiar buffer y redirigir inmediatamente
            ob_end_clean();
            header("Location: ../pages/Admin/tecnico.php?action=added&table=tecnico");
            exit();
        } else {
            throw new Exception("Error al guardar los datos: " . $stmt->error);
        }
    }
    
    $nombre = isset($_POST['nombre_tecnico']) ? trim($_POST['nombre_tecnico']) : '';

    if (!preg_match('/^[A-Za-zÁ-Úá-ú\s]+$/', $nombre)) {
        // Mostrar error si no cumple con el patrón
        $errores[] = "El nombre solo puede contener letras y espacios";
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    // Limpiar buffer y redirigir
    ob_end_clean();
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Limpiar buffer antes de mostrar el HTML
ob_end_flush();
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
                        Editando tabla: Técnico
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-10 text-red-500"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_tecnico" name="nombre_tecnico" 
                        value="<?= htmlspecialchars($nombre_tecnico) ?>"
                        pattern="[A-Za-zÁ-Úá-ú\s]+"
                        title="Solo se permiten letras y espacios"
                        oninput="validarSoloLetras(this)"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre" required />
                    <label for="nombre_tecnico"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>
 
                <!-- DNI -->
                <div class="relative">
                    <input type="text" id="dni_tecnico" name="dni_tecnico" 
                    value="<?= htmlspecialchars($dni_tecnico) ?>"
                    class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                    placeholder="DNI" 
                    required 
                    maxlength="8"
                    pattern="[0-9]{8}"
                    onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                    oninput="validarDNI(this)"/>
                    <label for="dni_tecnico"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        DNI (8 dígitos)
                    </label>
                </div>

                <!-- Edad -->
                <div id="input" class="relative">
                    <input type="number" id="edad_tecnico" name="edad_tecnico" 
                        value="<?= htmlspecialchars($edad_tecnico) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Edad" required 
                        min="17" max="99" maxlength="2"
                        oninput="this.value=this.value.slice(0,2); if(this.value<17)this.value=17; if(this.value>99)this.value=99"
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                    <label for="edad_tecnico"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Edad (17-99)
                    </label>
                </div>

                <!-- Número de Teléfono -->
                <div class="relative">
                    <input type="tel" id="num_telef" name="num_telef" 
                        value="<?= htmlspecialchars($num_telef) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Número de Teléfono" required 
                        pattern="[0-9]{9}" maxlength="9"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"
                        onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                    <label for="num_telef"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Número de Teléfono (9 dígitos)
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
    function validarSoloLetras(input) {
    // Expresión regular que permite letras (mayúsculas y minúsculas), espacios y acentos
    input.value = input.value.replace(/[^A-Za-zÁ-Úá-ú\s]/g, '');
    
    // Opcional: convertir primera letra de cada palabra a mayúscula
    input.value = input.value.replace(/\b\w/g, l => l.toUpperCase());
    }
        
    // Validación del campo DNI (8 dígitos)
    function validarDNI(input) {
        // Elimina cualquier carácter que no sea número
        input.value = input.value.replace(/[^0-9]/g, '');
        
        // Limita a 8 dígitos
        input.value = input.value.slice(0, 8);
    }

    // Validación del formulario completo
    document.querySelector('form').addEventListener('submit', function(e) {
        // Validación de DNI
        const dniInput = document.getElementById('dni_tecnico');
        if (dniInput.value.length !== 8) {
            alert('El DNI debe tener exactamente 8 dígitos');
            dniInput.focus();
            e.preventDefault();
            return;
        }

        // Validación de edad
        const edadInput = document.getElementById('edad_tecnico');
        const edad = parseInt(edadInput.value);
        if (isNaN(edad) || edad < 17 || edad > 99) {
            alert('La edad debe ser un número entre 17 y 99 años');
            edadInput.focus();
            e.preventDefault();
            return;
        }

        // Validación de teléfono
        const telefonoInput = document.getElementById('num_telef');
        if (telefonoInput.value.length !== 9) {
            alert('El teléfono debe tener exactamente 9 dígitos');
            telefonoInput.focus();
            e.preventDefault();
            return;
        }
    });
</script>

</body>
</html>
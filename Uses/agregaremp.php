<?php
include '../conexion.php';
session_start();

// Iniciar buffer de salida
ob_start();

// Inicializar variables del formulario
$nombre = $ruc = $servicio_empresa = "";

try {
    // Verificar la conexión a la base de datos
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Procesar el formulario cuando se envíe
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validar y sanitizar datos
        $nombre = isset($_POST['nombre']) ? trim(htmlspecialchars($_POST['nombre'])) : '';
        $ruc = isset($_POST['ruc']) ? trim(htmlspecialchars($_POST['ruc'])) : '';
        $servicio_empresa = isset($_POST['servicio_empresa']) ? trim(htmlspecialchars($_POST['servicio_empresa'])) : '';
        
        // Validar campos obligatorios
        if (empty($nombre)) {
            throw new Exception("El nombre de la empresa es requerido");
        }
        
        if (!preg_match('/^[A-Za-zÁ-Úá-úñÑ\s]+$/', $nombre)) {
            throw new Exception("El nombre solo puede contener letras y espacios");
        }
        
        if (empty($ruc)) {
            throw new Exception("El RUC de la empresa es requerido");
        }
        
        if (!preg_match('/^\d+$/', $ruc)) {
            throw new Exception("El RUC solo puede contener números");
        }
        
        if (empty($servicio_empresa)) {
            throw new Exception("El servicio de la empresa es requerido");
        }

        // Validar que el servicio no contenga símbolos
        if (preg_match('/[^A-Za-zÁ-Úá-úñÑ0-9\s,.]/', $servicio_empresa)) {
            throw new Exception("El servicio no puede contener símbolos especiales");
        }

        // Preparar consulta SQL
        $sql = "INSERT INTO tbl_empresa (nombre, ruc, servicio_empresa) VALUES (?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt->bind_param("sss", $nombre, $ruc, $servicio_empresa);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Empresa agregada correctamente';
            // Limpiar buffer y redirigir inmediatamente
            ob_end_clean();
            header("Location: ../pages/Admin/empresa.php");
            exit();
        } else {
            throw new Exception("Error al guardar los datos: " . $stmt->error);
        }
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
                        Agregando a tabla: Empresa
                    </div>
                </div>
            </div>
        </div>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" id="formEmpresa">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre" name="nombre"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre" 
                        value="<?php echo htmlspecialchars($nombre); ?>" 
                        required
                        pattern="[A-Za-zÁ-Úá-úñÑ\s]+"
                        title="Solo se permiten letras y espacios"
                        onkeypress="return validarLetras(event)"
                        onpaste="return false;">
                    <label for="nombre"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>
 
                <!-- RUC -->
                <div class="relative">
                    <input type="text" id="ruc" name="ruc" 
                    value="<?= htmlspecialchars($ruc) ?>"
                    class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                    placeholder="RUC" required 
                    pattern="\d+"
                    title="Solo se permiten números"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    onpaste="return false;">
                    <label for="ruc"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        RUC
                    </label>
                </div>

                <!-- Servicio -->
                <div id="input" class="relative col-span-2">
                    <textarea id="servicio_empresa" name="servicio_empresa"
                        class="block w-full text-sm px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto resize-none"
                        placeholder="Servicio" required
                        oninput="autoResize(this); validarServicio(this)"
                        onkeypress="return validarLetrasServicio(event)"
                        onpaste="return false;"><?= htmlspecialchars($servicio_empresa) ?></textarea>
                    <label for="servicio_empresa"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Servicio
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
                <button type="button"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300"
                    onclick="window.history.back();">
                    <div class="flex gap-2 items-center">Cancelar</div>
                </button>
            </div>
        </form>
    </div>

    <script>
    // Función para autoajustar el textarea
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }

    // Validar solo letras (incluye acentos y ñ) para el campo nombre
    function validarLetras(event) {
        const tecla = String.fromCharCode(event.keyCode || event.which);
        const letras = /^[A-Za-zÁ-Úá-úñÑ\s]$/;
        
        // Permitir teclas de control
        if (event.ctrlKey || event.altKey || event.metaKey) return true;
        if ([8, 9, 13, 32].includes(event.keyCode)) return true; // backspace, tab, enter, space
        
        if (!letras.test(tecla)) {
            event.preventDefault();
            return false;
        }
        return true;
    }

    // Validar letras para el campo servicio (permite números también)
    function validarLetrasServicio(event) {
        const tecla = String.fromCharCode(event.keyCode || event.which);
        const permitidas = /^[A-Za-zÁ-Úá-úñÑ0-9\s,.]$/;
        
        // Permitir teclas de control
        if (event.ctrlKey || event.altKey || event.metaKey) return true;
        if ([8, 9, 13, 32].includes(event.keyCode)) return true; // backspace, tab, enter, space
        
        if (!permitidas.test(tecla)) {
            event.preventDefault();
            return false;
        }
        return true;
    }

    // Validar el contenido del servicio mientras se escribe
    function validarServicio(textarea) {
        // Eliminar símbolos especiales (mantiene letras, números, espacios, comas y puntos)
        textarea.value = textarea.value.replace(/[^A-Za-zÁ-Úá-úñÑ0-9\s,.]/g, '');
    }

    // Validación del formulario
    document.getElementById('formEmpresa').addEventListener('submit', function(e) {
        const nombre = document.getElementById('nombre');
        const ruc = document.getElementById('ruc');
        const servicio = document.getElementById('servicio_empresa');
        
        // Validación adicional por si acaso
        if (!/^[A-Za-zÁ-Úá-úñÑ\s]+$/.test(nombre.value)) {
            alert('El nombre solo puede contener letras y espacios');
            e.preventDefault();
            return;
        }
        
        if (!/^\d+$/.test(ruc.value)) {
            alert('El RUC solo puede contener números');
            e.preventDefault();
            return;
        }
        
        if (servicio.value.trim() === '') {
            alert('El servicio es requerido');
            e.preventDefault();
            return;
        }

        // Validar que el servicio no contenga símbolos
        if (/[^A-Za-zÁ-Úá-úñÑ0-9\s,.]/.test(servicio.value)) {
            alert('El servicio no puede contener símbolos especiales');
            e.preventDefault();
            return;
        }
    });

    // Aplicar auto-resize cuando se carga la página
    document.addEventListener("DOMContentLoaded", function() {
        const textarea = document.getElementById('servicio_empresa');
        autoResize(textarea);
    });

    // Prevenir pegado de texto en todos los campos
    document.querySelectorAll('input, textarea').forEach(element => {
        element.addEventListener('paste', function(e) {
            e.preventDefault();
            return false;
        });
    });
    </script>
</body>
</html>
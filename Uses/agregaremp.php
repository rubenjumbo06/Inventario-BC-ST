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
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $ruc = isset($_POST['ruc']) ? trim($_POST['ruc']) : '';
        $servicio_empresa = isset($_POST['servicio_empresa']) ? trim($_POST['servicio_empresa']) : '';
        
        // Validaciones básicas
        if (empty($nombre)) {
            throw new Exception("El nombre de la empresa es requerido");
        }
        if (!preg_match("/^[a-zA-Z0-9\s]+$/", $nombre)) {
            throw new Exception("El nombre solo puede contener letras, números y espacios");
        }

        if (empty($ruc)) {
            throw new Exception("El RUC de la empresa es requerido");
        }
        if (!preg_match("/^[0-9]{11}$/", $ruc)) {
            throw new Exception("El RUC debe contener exactamente 11 dígitos numéricos");
        }

        if (!empty($servicio_empresa) && !preg_match("/^[a-zA-Z0-9\s]+$/", $servicio_empresa)) {
            throw new Exception("El servicio solo puede contener letras, números y espacios");
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
            header("Location: ../pages/Admin/empresa.php?action=added&table=empresa");
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
    <title>Agregar Empresa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]">Agregar Empresa</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Agregando a tabla: Empresa
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre" name="nombre" 
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre" 
                        value="<?= htmlspecialchars($nombre) ?>" 
                        required
                        pattern="[A-Za-z0-9\s]+"
                        title="Solo se permiten letras, números y espacios"
                        oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s]/g, '')"/>
                    <label for="nombre"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Nombre
                    </label>
                </div>

                <!-- RUC -->
                <div id="input" class="relative">
                    <input type="text" id="ruc" name="ruc" 
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="RUC (11 dígitos)" 
                        value="<?= htmlspecialchars($ruc) ?>" 
                        required
                        pattern="[0-9]{11}"
                        maxlength="11"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                        title="Solo se permiten 11 dígitos numéricos"/>
                    <label for="ruc"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        RUC (11 dígitos)
                    </label>
                </div>
                
                <!-- Servicio -->
                <div id="input" class="relative col-span-2">
                    <textarea id="servicio_empresa" name="servicio_empresa"
                        class="block w-full text-sm px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto resize-none"
                        placeholder="Servicio"
                        oninput="autoResize(this); this.value = this.value.replace(/[^a-zA-Z0-9\s]/g, '');"><?= htmlspecialchars($servicio_empresa) ?></textarea>
                    <label for="servicio_empresa"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
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
    // Función para autoajustar la altura del textarea
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.max(textarea.scrollHeight, 50) + 'px'; // Altura mínima de 50px
    }

    // Validación para campos de texto (solo letras, números y espacios)
    function validarTexto(input) {
        input.value = input.value.replace(/[^a-zA-Z0-9\s]/g, '');
    }

    // Aplicar validaciones y auto-resize al cargar la página
    document.addEventListener("DOMContentLoaded", function() {
        // Configuración para nombre
        const nombreInput = document.getElementById('nombre');
        nombreInput.addEventListener("input", function() {
            validarTexto(this);
        });

        // Configuración para ruc
        const rucInput = document.getElementById('ruc');
        rucInput.addEventListener("input", function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
        });

        // Configuración para servicio_empresa
        const textarea = document.getElementById('servicio_empresa');
        if (textarea) {
            autoResize(textarea); // Ajuste inicial
            if (textarea.value) {
                textarea.dispatchEvent(new Event('input')); // Ajustar label si hay contenido
            }
        }
    });
    </script>
</body>
</html>
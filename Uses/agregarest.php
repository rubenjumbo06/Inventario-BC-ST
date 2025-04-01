<?php
include '../conexion.php';
session_start();

// Iniciar buffer de salida
ob_start();

// Inicializar variables del formulario
$nombre_estado = $descripcion = "";

try {
    // Verificar la conexión a la base de datos
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Procesar el formulario cuando se envíe
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validar y sanitizar datos
        $nombre_estado = isset($_POST['nombre_estado']) ? trim(htmlspecialchars($_POST['nombre_estado'])) : '';
        $descripcion = isset($_POST['descripcion']) ? trim(htmlspecialchars($_POST['descripcion'])) : '';

        // Validar campos obligatorios
        if (empty($nombre_estado)) {
            throw new Exception("El nombre del estado es requerido");
        }
        
        if (empty($descripcion)) {
            throw new Exception("La descripción es requerida");
        }

        // Preparar consulta SQL
        $sql = "INSERT INTO tbl_estados (nombre_estado, descripcion) VALUES (?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $nombre_estado, $descripcion);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Estado agregado correctamente';
            // Limpiar buffer y redirigir inmediatamente
            ob_end_clean();
            header("Location: ../pages/Admin/estados.php");
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
                        Editando tabla: Estados
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-10 text-red-500"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="grid grid-cols-1 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre_estado" name="nombre_estado" value="<?= htmlspecialchars($nombre_estado) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre" required />
                    <label for="nombre_estado"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scalechés-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombre
                    </label>
                </div>
 
                <!-- Descripcion -->
                <div id="input" class="relative">
                    <textarea id="descripcion" name="descripcion"
                        class="block w-full text-sm px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto resize-none"
                        placeholder="Descripción" required
                        oninput="autoResize(this)"><?= htmlspecialchars($descripcion) ?></textarea>
                    <label for="descripcion"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Descripción
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
    // Función para autoajustar el tamaño del textarea
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }

    // Validación para campos de texto (solo letras, números y espacios)
    function validarTexto(input) {
        input.value = input.value.replace(/[^a-zA-Z0-9\s]/g, '');
    }

    // Aplicar validaciones y auto-resize al cargar la página
    document.addEventListener("DOMContentLoaded", function() {
        const textarea = document.getElementById('descripcion');
        autoResize(textarea);

        // Validación para nombre_estado
        document.getElementById('nombre_estado').addEventListener('input', function() {
            validarTexto(this);
        });

        // Validación para descripcion
        document.getElementById('descripcion').addEventListener('input', function() {
            validarTexto(this);
            autoResize(this); // Mantener el autoajuste después de la validación
        });
    });
    </script>
</body>
</html>
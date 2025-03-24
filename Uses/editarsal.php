<?php
require_once("../conexion.php");
session_start();

// Función para redirección basada en rol
function redirectBasedOnRole() {
    if (isset($_SESSION['role'])) {
        $role = $_SESSION['role'];
        $page = ($role == 'admin') ? '../pages/Admin/salidas.php' : 
                (($role == 'user') ? '../pages/Usuario/salidas.php' : 
                '../pages/Tecnico/salidas.php');
        header("Location: " . $page);
        exit();
    }
    header("Location: ../pages/salidas.php");
    exit();
}

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

try {
    // Verificar que el parámetro id_salidas esté presente y sea numérico
    if (!isset($_GET['id_salidas']) || !is_numeric($_GET['id_salidas'])) {
        throw new Exception("ID inválido o no proporcionado.");
    }

    $id_salidas = intval($_GET['id_salidas']);
    
    // Obtener los datos de la salida con información del usuario
    $sql = "SELECT s.*, u.username, u.nombre as nombre_usuario 
            FROM tbl_reg_salidas s
            LEFT JOIN tbl_users u ON s.id_user = u.id_user
            WHERE s.id_salidas = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_salidas);
    $stmt->execute();
    $result = $stmt->get_result();
    $salidas = $result->fetch_assoc();

    // Verificar si se encontró la salida
    if (!$salidas) {
        throw new Exception("Salida no encontrada.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar y sanitizar inputs
        $titulo = trim($_POST['titulo'] ?? '');
        $Destino = trim($_POST['Destino'] ?? '');
        $body = trim($_POST['body'] ?? '');
        
        // Validar que solo contengan texto (letras, espacios y algunos caracteres básicos)
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\.,;:¿?¡!\-]+$/', $titulo)) {
            throw new Exception("El título solo puede contener letras y signos básicos de puntuación.");
        }
        
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\.,;:¿?¡!\-]+$/', $Destino)) {
            throw new Exception("El destino solo puede contener letras y signos básicos de puntuación.");
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\.,;:¿?¡!\-]+$/', $body)) {
            throw new Exception("El cuerpo solo puede contener letras y signos básicos de puntuación.");
        }

        // Actualizar solo los campos editables
        $sql = "UPDATE tbl_reg_salidas SET titulo=?, Destino=?, body=? WHERE id_salidas=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $titulo, $Destino, $body, $id_salidas);

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Salida actualizada correctamente";
            redirectBasedOnRole();
        } else {
            throw new Exception("Error al actualizar la salida: " . $stmt->error);
        }
    }
} catch (Exception $e) {
    $mensaje_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Registro de Salida</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
    <script>
        function validarTexto(input) {
            // Permite letras, espacios, acentos y signos básicos de puntuación
            input.value = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s\.,;:¿?¡!\-]/g, '');
        }
    </script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]">Editar Registro de Salida</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Editando tabla: Registro de Salidas
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($mensaje_error)): ?>
            <div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg">
                <?php echo htmlspecialchars($mensaje_error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Items (solo lectura) -->
                <div id="input" class="relative">
                    <input type="text" id="items" name="items" 
                           value="<?= htmlspecialchars($salidas['items'] ?? '') ?>" 
                           class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-gray-100 rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                           readonly />
                    <label for="items"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-gray-100 px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Items
                    </label>
                </div>

                <!-- Usuario (solo lectura) -->
                <div id="input" class="relative">
                    <input type="text" id="usuario" name="usuario" 
                           value="<?= htmlspecialchars(($salidas['nombre_usuario'] ?? '') . ' (' . ($salidas['username'] ?? '') . ')') ?>" 
                           class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-gray-100 rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                           readonly />
                    <label for="usuario"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-gray-100 px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Usuario
                    </label>
                </div>

                <!-- Título -->
                <div id="input" class="relative col-span-2">
                    <input type="text" id="titulo" name="titulo" 
                           value="<?= htmlspecialchars($salidas['titulo'] ?? '') ?>"
                           class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                           placeholder="Título"
                           oninput="validarTexto(this)"
                           required />
                    <label for="titulo"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Título
                    </label>
                </div>

                <!-- Destino -->
                <div id="input" class="relative col-span-2">
                    <textarea id="Destino" name="Destino" 
                        class="block w-full text-sm h-[100px] px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto pr-[48px]"
                        placeholder="Destino"
                        oninput="validarTexto(this)"
                        required><?= htmlspecialchars($salidas['Destino'] ?? '') ?></textarea>
                    <label for="Destino"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Destino
                    </label>
                </div>

                <!-- Body -->
                <div id="input" class="relative col-span-2">
                    <textarea id="body" name="body" 
                        class="block w-full text-sm h-[100px] px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto pr-[48px]"
                        placeholder="Descripción"
                        oninput="validarTexto(this)"
                        readonly><?= htmlspecialchars($salidas['body'] ?? '') ?></textarea>
                    <label for="body"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Descripción
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
                <button type="button"
                    class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300"
                    onclick="window.history.back();">
                    <div class="flex gap-2 items-center">Cancelar</div>
                </button>
            </div>
        </form>
    </div>
</body>
</html>
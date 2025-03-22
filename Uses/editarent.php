<?php
require_once("../conexion.php");

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (isset($_GET['id_entradas']) && is_numeric($_GET['id_entradas'])) {
    $id_entradas = intval($_GET['id_entradas']);
    $sql = "SELECT * FROM tbl_reg_entradas WHERE id_entradas = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_entradas);
    $stmt->execute();
    $result = $stmt->get_result();
    $entrada = $result->fetch_assoc();

    if (!$entrada) {
        die("Entrada no encontrada.");
    }
} else {
    die("ID inválido.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los valores del formulario
    $items = $_POST['items'] ?? null;
    $titulo = $_POST['titulo'] ?? null;
    $body = $_POST['body'] ?? null;
    $id_user = $_POST['id_user'] ?? null;

    // Construir la consulta SQL dinámicamente
    $sql = "UPDATE tbl_reg_entradas SET ";
    $params = [];
    $types = "";

    if (!empty($items)) {
        $sql .= "items=?, ";
        $params[] = $items;
        $types .= "i";
    }
    if (!empty($titulo)) {
        $sql .= "titulo=?, ";
        $params[] = $titulo;
        $types .= "s";
    }
    if (!empty($body)) {
        $sql .= "body=?, ";
        $params[] = $body;
        $types .= "s";
    }
    if (!empty($id_user)) {
        $sql .= "id_user=?, ";
        $params[] = $id_user;
        $types .= "i";
    }

    // Eliminar la última coma y espacio
    $sql = rtrim($sql, ", ");

    // Agregar la condición WHERE
    $sql .= " WHERE id_entradas=?";
    $params[] = $id_entradas;
    $types .= "i";

    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo "<script>window.location.href='../pages/entradas.php';</script>";
    } else {
        echo "<script>alert('Error al actualizar la entrada');</script>";
        echo $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Registro de Entrada</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]">Editar Registro de Entrada</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Editando tabla: Registro de Entradas
                    </div>
                </div>
            </div>
        </div>

        <form method="POST">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Items -->
                <div id="input" class="relative">
                    <input type="number" id="items" name="items" value="<?= htmlspecialchars($entrada['items']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Items"/>
                    <label for="items"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Items
                    </label>
                </div>

                <!-- Título -->
                <div id="input" class="relative">
                    <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($entrada['titulo']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Título"/>
                    <label for="titulo"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Título
                    </label>
                </div>

                <!-- Body -->
                <div id="input" class="relative col-span-2">
                    <textarea id="body" name="body" 
                        class="block w-full text-sm h-[100px] px-4 py-2 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-auto pr-[48px]"
                        placeholder="Descripción"><?= htmlspecialchars($entrada['body']) ?></textarea>
                    <label for="body"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Descripción
                    </label>
                </div>

                <!-- Usuario -->
                <div id="input" class="relative">
                    <select name="id_user" id="user_select" 
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
                        <option value="" disabled selected>Selecciona un Usuario</option>
                    </select>
                    <label for="id_user"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Usuario
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
    document.addEventListener("DOMContentLoaded", function () {
        function cargarDatos(endpoint, selectId, selectedValue) {
            fetch(endpoint)
                .then(response => response.json())
                .then(data => {
                    if (!Array.isArray(data)) {
                        console.error("Error: Respuesta no válida", data);
                        return;
                    }
                    let select = document.getElementById(selectId);
                    let placeholderText = "Selecciona un Usuario";
                    select.innerHTML = <option value="" disabled selected>${placeholderText}</option>;

                    data.forEach(item => {
                        let option = document.createElement("option");
                        option.value = item.id_user;
                        option.textContent = item.nombre || item.username; // Ajusta según el campo en tu tabla de usuarios
                        if (option.value == selectedValue) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                })
                .catch(error => console.error("Error cargando los datos:", error));
        }

        cargarDatos("get_users.php", "user_select", "<?= $entrada['id_user'] ?>");
    });
    </script>
</body>
</html>
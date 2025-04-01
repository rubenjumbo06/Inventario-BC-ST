<?php
require_once("../conexion.php");

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (isset($_GET['id_user']) && is_numeric($_GET['id_user'])) {
    $id_user = intval($_GET['id_user']);
    $sql = "SELECT * FROM tbl_users WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        die("Usuario no encontrado.");
    }
} else {
    die("ID inválido.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los valores del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    // Validaciones en el backend
    if (!empty($nombre) && !preg_match("/^[a-zA-Z0-9\s]+$/", $nombre)) {
        die("El nombre solo puede contener letras, números y espacios.");
    }
    if (!empty($apellidos) && !preg_match("/^[a-zA-Z0-9\s]+$/", $apellidos)) {
        die("Los apellidos solo pueden contener letras, números y espacios.");
    }
    if (!empty($username) && !preg_match("/^[a-zA-Z0-9\s]+$/", $username)) {
        die("El username solo puede contener letras, números y espacios.");
    }
    if (!empty($telefono) && (!preg_match("/^[0-9]{9}$/", $telefono))) {
        die("El teléfono debe contener exactamente 9 dígitos numéricos.");
    }
    if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        die("El correo no tiene un formato válido.");
    }

    // Construir la consulta SQL dinámicamente
    $sql = "UPDATE tbl_users SET ";
    $params = [];
    $types = "";

    if (!empty($nombre)) {
        $sql .= "nombre=?, ";
        $params[] = $nombre;
        $types .= "s";
    }
    if (!empty($apellidos)) {
        $sql .= "apellidos=?, ";
        $params[] = $apellidos;
        $types .= "s";
    }
    if (!empty($username)) {
        $sql .= "username=?, ";
        $params[] = $username;
        $types .= "s";
    }
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= "password=?, ";
        $params[] = $hashed_password;
        $types .= "s";
    }
    if (!empty($role)) {
        $sql .= "role=?, ";
        $params[] = $role;
        $types .= "s";
    }
    if (!empty($correo)) {
        $sql .= "correo=?, ";
        $params[] = $correo;
        $types .= "s";
    }
    if (!empty($telefono)) {
        $sql .= "telefono=?, ";
        $params[] = $telefono;
        $types .= "s";
    }

    // Agregar fecha_modificacion con la hora actual
    $sql .= "fecha_modificacion=CURRENT_TIMESTAMP, ";
    $sql = rtrim($sql, ", ");
    $sql .= " WHERE id_user=?";
    $params[] = $id_user;
    $types .= "i";

    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo "<script>window.location.href='../pages/Admin/users.php';</script>";
    } else {
        echo "<script>alert('Error al actualizar el usuario');</script>";
        echo $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]">Editar Usuario</div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        Editando tabla: Usuarios
                    </div>
                </div>
            </div>
        </div>

        <form method="POST">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Nombre -->
                <div id="input" class="relative">
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombre"
                        pattern="[A-Za-z0-9\s]+"
                        title="Solo se permiten letras, números y espacios"/>
                    <label for="nombre"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Nombre
                    </label>
                </div>

                <!-- Apellidos -->
                <div id="input" class="relative">
                    <input type="text" id="apellidos" name="apellidos" value="<?= htmlspecialchars($user['apellidos']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Apellidos"
                        pattern="[A-Za-z0-9\s]+"
                        title="Solo se permiten letras, números y espacios"/>
                    <label for="apellidos"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Apellidos
                    </label>
                </div>

                <!-- Username -->
                <div id="input" class="relative">
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Username"
                        pattern="[A-Za-z0-9\s]+"
                        title="Solo se permiten letras, números y espacios"/>
                    <label for="username"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Username
                    </label>
                </div>

                <!-- Password -->
                <div id="input" class="relative">
                    <input type="password" id="password" name="password"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nueva Contraseña"/>
                    <label for="password"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Nueva Contraseña
                    </label>
                </div>

                <!-- Role -->
                <div id="input" class="relative">
                    <select name="role" id="role"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-hidden pr-[48px]">
                        <option value="" disabled selected>Selecciona un Rol</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="tecnico" <?= $user['role'] === 'tecnico' ? 'selected' : '' ?>>Técnico</option>
                    </select>
                    <label for="role"
                        class="absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Rol
                    </label>
                </div>

                <!-- Correo -->
                <div id="input" class="relative">
                    <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($user['correo']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Correo"
                        pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
                        title="Ingrese un correo válido (ej. usuario@dominio.com)"/>
                    <label for="correo"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Correo
                    </label>
                </div>

                <!-- Teléfono -->
                <div id="input" class="relative">
                    <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($user['telefono']) ?>"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Teléfono (9 dígitos)"
                        pattern="[0-9]{9}"
                        maxlength="9"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)"
                        title="Solo se permiten 9 dígitos numéricos"/>
                    <label for="telefono"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] start-1">
                        Teléfono (9 dígitos)
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
    // Validación para campos de texto (solo letras, números y espacios)
    function validarTexto(input) {
        input.value = input.value.replace(/[^a-zA-Z0-9\s]/g, '');
    }

    // Validación para correo (formato de email válido)
    function validarCorreo(input) {
        input.value = input.value.replace(/[^a-zA-Z0-9@._-]/g, '');
    }

    // Aplicar validaciones al cargar la página
    document.addEventListener("DOMContentLoaded", function () {
        // Validación para nombre
        document.getElementById('nombre').addEventListener('input', function() {
            validarTexto(this);
        });

        // Validación para apellidos
        document.getElementById('apellidos').addEventListener('input', function() {
            validarTexto(this);
        });

        // Validación para username
        document.getElementById('username').addEventListener('input', function() {
            validarTexto(this);
        });

        // Validación para correo
        document.getElementById('correo').addEventListener('input', function() {
            validarCorreo(this);
        });

        // Validación para teléfono (ya está en el HTML con oninput)
    });
    </script>
</body>
</html>
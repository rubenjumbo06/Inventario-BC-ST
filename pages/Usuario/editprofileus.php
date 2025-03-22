<?php
session_start();
include '../../conexion.php';
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
// Obtener datos del usuario autenticado
$username = $_SESSION['username'];
$query = "SELECT id_user, nombre, apellidos, username, correo, telefono FROM tbl_users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $id_user = $user['id_user'];
    $nombre = $user['nombre'];
    $apellidos = $user['apellidos'];
    $username = $user['username'];
    $correo = $user['correo'];
    $telefono = $user['telefono'];
} else {
    $mensaje = "Usuario no encontrado.";
    header("Location: perfilus.php");
    exit();
}
$stmt->close();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim(htmlspecialchars($_POST['nombre']));
    $apellidos = trim(htmlspecialchars($_POST['apellidos']));
    $username = trim(htmlspecialchars($_POST['username']));
    $correo = trim(htmlspecialchars($_POST['correo']));
    $telefono = trim(htmlspecialchars($_POST['telefono']));
    $password = $_POST['password'];

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo electrónico no es válido.";
    } elseif (!preg_match('/^[0-9]{9}$/', $telefono)) {
        $mensaje = "El número de teléfono debe tener 9 dígitos.";
    } elseif (!empty($id_user) && !empty($nombre) && !empty($apellidos) && !empty($username) && !empty($correo) && !empty($telefono)) {

        if (!empty($password)) {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE tbl_users SET nombre = ?, apellidos = ?, username = ?, password = ?, correo = ?, telefono = ? WHERE id_user = ?";
            $params = [$nombre, $apellidos, $username, $hashed_password, $correo, $telefono, $id_user];
        } else {

            $sql = "UPDATE tbl_users SET nombre = ?, apellidos = ?, username = ?, correo = ?, telefono = ? WHERE id_user = ?";
            $params = [$nombre, $apellidos, $username, $correo, $telefono, $id_user];
        }


        if ($stmt = $conn->prepare($sql)) {

            $types = str_repeat('s', count($params) - 1) . 'i';
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $mensaje = "¡Datos actualizados correctamente!";
                header("Location: perfilus.php"); 
                exit();
            } else {
                $mensaje = "Error al actualizar los datos: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $mensaje = "Error al preparar la consulta: " . $conn->error;
        }
    } else {
        $mensaje = "Todos los campos son obligatorios.";
    }
}
// Obtener datos del usuario autenticado
$username = $_SESSION['username'];
$query = "SELECT nombre, apellidos, role FROM tbl_users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $nombre = $user['nombre'];
    $apellidos = $user['apellidos'];
    $rol = $user['role'];
} else {
    $nombre = "Usuario";
    $apellidos = "Desconocido";
    $rol = "Sin rol";
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../assets/CSS/agg.css">
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">
    <div class="p-10 rounded-lg shadow-lg">
        <div class="flex flex-wrap gap-5 items-center w-full max-md:max-w-full mb-10">
            <div class="flex flex-wrap flex-1 shrink gap-5 items-center self-stretch my-auto basis-0 min-w-[240px] max-md:max-w-full">
                <div class="flex relative flex-col justify-center items-center bg-gray-100 h-[70px] w-[70px] rounded-[16px] overflow-hidden">
                    <img src="../../assets/img/p2.jpeg" alt="Editar Listas" class="w-full h-full object-cover">
                </div>
                <div class="flex flex-col self-stretch my-auto min-w-[240px]">
                    <strong>
                        <div class="text-base text-[var(--verde-oscuro)]"><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></div>
                    </strong>
                    <div class="mt-2 text-sm text-[var(--verde-oscuro)]">
                        <?php echo htmlspecialchars($rol); ?>
                    </div>
                </div>
            </div>
        </div>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="grid grid-cols-2 gap-6 mb-10">
                <!-- Campo Nombres -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="nombre"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Nombres" value="<?php echo htmlspecialchars($nombre); ?>" required />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Nombres
                    </label>
                </div>

                <!-- Campo Apellidos -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="apellidos"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Apellidos" value="<?php echo htmlspecialchars($apellidos); ?>" required />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Apellidos
                    </label>
                </div>

                <!-- Campo Username -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="username"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Username
                    </label>
                </div>

                <!-- Campo Password -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="password"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Contraseña" />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Contraseña
                    </label>
                </div>

                <!-- Campo Correo Electrónico -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="correo"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Correo Electrónico" value="<?php echo htmlspecialchars($correo); ?>" required />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Correo Electrónico
                    </label>
                </div>

                <!-- Campo Número de Teléfono -->
                <div id="input" class="relative">
                    <input type="text" id="floating_outlined" name="telefono"
                        class="block w-full text-sm h-[50px] px-4 text-slate-900 bg-white rounded-[8px] border border-violet-200 appearance-none focus:border-transparent focus:outline focus:outline-primary focus:ring-0 hover:border-brand-500-secondary peer invalid:border-error-500 invalid:focus:border-error-500 overflow-ellipsis overflow-hidden text-nowrap pr-[48px]"
                        placeholder="Número de Teléfono" value="<?php echo htmlspecialchars($telefono); ?>" required />
                    <label for="floating_outlined"
                        class="peer-placeholder-shown:-z-10 peer-focus:z-10 absolute text-[14px] leading-[150%] text-primary peer-focus:text-primary peer-invalid:text-error-500 focus:invalid:text-error-500 duration-300 transform -translate-y-[1.2rem] scale-75 top-2 z-10 origin-[0] bg-white disabled:bg-gray-50-background- px-2 peer-focus:px-2 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-[1.2rem] rtl:peer-focus:translate-x-1/4 rtl:peer-focus:left-auto start-1">
                        Número de Teléfono
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
                <a href="perfilus.php">
                    <button type="button"
                        class="w-fit rounded-lg text-sm px-6 py-3 h-[50px] border border-[var(--verde-oscuro)] text-[var(--verde-oscuro)] font-semibold shadow-md hover:bg-red-500 hover:text-white transition-all duration-300">
                        <div class="flex gap-2 items-center">Cancelar</div>
                    </button>
                </a>
            </div>
        </form>
    </div>
</body>
</html>
<?php
$action = isset($_GET['action']) ? $_GET['action'] : '';
$table = isset($_GET['table']) ? $_GET['table'] : '';
$message = '';
$show_notification = false;

// Mapear nombres de tablas a versiones en singular y con mayúscula inicial
$table_names = [
    'entradas' => 'Entrada',
    'activos' => 'Activo',
    'herramientas' => 'Herramienta',
    'consumibles' => 'Consumible',
    'empresa' => 'Empresa',
    'utilidad' => 'Utilidad',
    'estados' => 'Estado',
    'users' => 'Usuario',
    'salidas' => 'Salida',
    'tecnico' => 'Técnico'
];

// Determinar el nombre de la tabla en singular (por defecto "Registro" si no está en la lista)
$table_name = isset($table_names[$table]) ? $table_names[$table] : 'Registro';

switch ($action) {
    case 'added':
        $message = "$table_name agregado correctamente";
        $show_notification = true;
        break;
    case 'updated':
        $message = "$table_name actualizado correctamente";
        $show_notification = true;
        break;
    case 'deleted':
        $message = "$table_name eliminado correctamente";
        $show_notification = true;
        break;
    case 'error':
        $message = isset($_GET['message']) ? urldecode($_GET['message']) : 'Error desconocido';
        $show_notification = true;
        break;
    default:
        $show_notification = false;
}
?>

<style>
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: rgb(255, 255, 255);
        color: rgb(98, 148, 110);
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        z-index: 1001;
    }
    .notification.hidden {
        display: none;
    }
    .notification.error {
        color: #dc3545;
    }
    .notification-close {
        cursor: pointer;
        font-weight: bold;
        padding: 0 0.5rem;
    }
    .notification-close:hover {
        color: #000;
    }
</style>

<div id="notification" class="notification <?php echo $show_notification ? '' : 'hidden'; ?> <?php echo $action === 'error' ? 'error' : ''; ?>">
    <span><?php echo $message; ?></span>
    <span class="notification-close" onclick="hideNotification()">X</span>
</div>

<script>
    function hideNotification() {
        document.getElementById('notification').classList.add('hidden');
    }

    <?php if ($show_notification): ?>
        setTimeout(() => {
            document.getElementById('notification').classList.remove('hidden');
            setTimeout(hideNotification, 5000); // Ocultar después de 5 segundos
        }, 100);
    <?php endif; ?>
</script>
<?php
require_once("../conexion.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_activos'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida o ID no proporcionado']);
    exit;
}

$id = $_POST['id_activos'];

// Iniciar transacción
$conn->begin_transaction();
try {
    // Obtener la fecha actual en formato DATE (YYYY-MM-DD)
    $fecha_eliminado = date('Y-m-d');

    // Ocultar el activo (cambiar id_status a 2 y actualizar fecha_eliminado)
    $sql = "UPDATE tbl_activos SET id_status = 2, fecha_eliminado = ? WHERE id_activos = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparando UPDATE tbl_activos: " . $conn->error);
    }
    $stmt->bind_param("si", $fecha_eliminado, $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al procesar: ' . $e->getMessage()]);
}

$conn->close();
?>
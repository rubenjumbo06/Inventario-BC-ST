<?php
require_once("../conexion.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_estado'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida o ID no proporcionado']);
    exit;
}

$id = $_POST['id_estado'];

// Iniciar transacción
$conn->begin_transaction();
try {
    // Ocultar el estado (cambiar id_status a 2)
    $sql = "UPDATE tbl_estados SET id_status = 2 WHERE id_estado = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparando UPDATE tbl_estados: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
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
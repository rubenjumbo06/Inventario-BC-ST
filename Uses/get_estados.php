<?php
include '../conexion.php';

$sql = "SELECT id_estado, nombre_estado FROM tbl_estados WHERE id_status = 1";
$result = $conn->query($sql);

$estados = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $estados[] = $row;
    }
}

echo json_encode($estados);
?>
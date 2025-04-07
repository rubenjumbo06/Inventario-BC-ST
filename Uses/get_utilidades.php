<?php
include '../conexion.php';

$sql = "SELECT id_utilidad, nombre_utilidad FROM tbl_utilidad WHERE id_status = 1";
$result = $conn->query($sql);

$utilidades = [];
while ($row = $result->fetch_assoc()) {
    $utilidades[] = $row;
}

echo json_encode($utilidades);
?>

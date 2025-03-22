<?php
require_once("../conexion.php");

try {
    // Obtener los valores del ENUM 'ubicacion_activos'
    $sql = "SHOW COLUMNS FROM tbl_activos LIKE 'ubicacion_activos'";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }

    $row = $result->fetch_assoc();
    $type = $row['Type'];

    // Extraer los valores del ENUM
    preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
    $enum_values = explode("','", $matches[1]);

    // Devolver los valores como JSON
    echo json_encode($enum_values);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
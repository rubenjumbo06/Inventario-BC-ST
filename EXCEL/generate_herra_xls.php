<?php
require '../vendor/autoload.php';
require '../conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Obtener filtros desde el formulario POST
$filter_nombre = isset($_POST['nombre_search']) && !empty($_POST['nombre_search']) ? $_POST['nombre_search'] : null;
$filter_empresa = isset($_POST['empresa']) && !empty($_POST['empresa']) ? $_POST['empresa'] : null;
$filter_estado = isset($_POST['estado']) && !empty($_POST['estado']) ? $_POST['estado'] : null;
$filter_utilidad = isset($_POST['utilidad']) && !empty($_POST['utilidad']) ? $_POST['utilidad'] : null;
$filter_ubicacion = isset($_POST['ubicacion']) && !empty($_POST['ubicacion']) ? $_POST['ubicacion'] : null;

// Crear una nueva hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir encabezados y estilos
$encabezados = ['ID', 'Nombre', 'Cantidad', 'Empresa', 'Estado', 'Utilidad', 'Ubicación', 'Ingreso'];
$columnas = range('A', 'H');

// Aplicar estilos a los encabezados
$sheet->getStyle('A1:H1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0073E6']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
]);

// Insertar encabezados
foreach ($encabezados as $index => $nombre) {
    $sheet->setCellValue($columnas[$index] . '1', $nombre);
}

// Construir la consulta SQL con JOINs y filtros
$sql = "SELECT h.id_herramientas, h.nombre_herramientas, h.cantidad_herramientas, 
        e.nombre AS nombre_empresa, 
        es.nombre_estado, 
        u.nombre_utilidad, 
        h.ubicacion_herramientas, h.fecha_ingreso 
        FROM tbl_herramientas h
        LEFT JOIN tbl_empresa e ON h.id_empresa = e.id_empresa
        LEFT JOIN tbl_estados es ON h.estado_herramientas = es.id_estado
        LEFT JOIN tbl_utilidad u ON h.utilidad_herramientas = u.id_utilidad
        WHERE 1=1 AND h.id_status = 1";
$params = [];
$types = "";

if ($filter_nombre) {
    $sql .= " AND h.nombre_herramientas LIKE ?";
    $params[] = "%$filter_nombre%";
    $types .= "s";
}
if ($filter_empresa) {
    $sql .= " AND e.nombre = ?";
    $params[] = $filter_empresa;
    $types .= "s";
}
if ($filter_estado) {
    $sql .= " AND es.nombre_estado = ?";
    $params[] = $filter_estado;
    $types .= "s";
}
if ($filter_utilidad) {
    $sql .= " AND u.nombre_utilidad = ?";
    $params[] = $filter_utilidad;
    $types .= "s";
}
if ($filter_ubicacion) {
    $sql .= " AND h.ubicacion_herramientas = ?";
    $params[] = $filter_ubicacion;
    $types .= "s";
}

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$fila = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['id_herramientas']);
    $sheet->setCellValue('B' . $fila, $row['nombre_herramientas']);
    $sheet->setCellValue('C' . $fila, $row['cantidad_herramientas']);
    $sheet->setCellValue('D' . $fila, $row['nombre_empresa']);
    $sheet->setCellValue('E' . $fila, $row['nombre_estado']);
    $sheet->setCellValue('F' . $fila, $row['nombre_utilidad']);
    $sheet->setCellValue('G' . $fila, $row['ubicacion_herramientas']);
    $sheet->setCellValue('H' . $fila, $row['fecha_ingreso']);
    $fila++;
}

// Aplicar bordes a la tabla
$ultimaFila = $fila - 1;
$sheet->getStyle("A1:H$ultimaFila")->applyFromArray([
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
    ]
]);

// Ajustar tamaño automático de las columnas
foreach ($columnas as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Descargar el archivo Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_herramientas.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
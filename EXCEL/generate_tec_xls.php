<?php
require '../vendor/autoload.php';
require '../conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Obtener filtro desde el formulario POST
$filter_search = isset($_POST['filter_search']) && !empty($_POST['filter_search']) ? $_POST['filter_search'] : null;

// Crear una nueva hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir encabezados y estilos
$encabezados = ['ID', 'Nombre', 'DNI', 'Edad', 'Número de Teléfono'];
$columnas = range('A', 'E');

// Aplicar estilos a los encabezados
$sheet->getStyle('A1:E1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0073E6']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
]);

// Insertar encabezados
foreach ($encabezados as $index => $nombre) {
    $sheet->setCellValue($columnas[$index] . '1', $nombre);
}

// Construir la consulta SQL con filtro
$sql = "SELECT id_tecnico, nombre_tecnico, dni_tecnico, edad_tecnico, num_telef 
        FROM tbl_tecnico 
        WHERE 1=1 AND id_status = 1";
$params = [];
$types = "";

if ($filter_search) {
    $sql .= " AND nombre_tecnico LIKE ?";
    $params[] = "%$filter_search%";
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
    $sheet->setCellValue('A' . $fila, $row['id_tecnico']);
    $sheet->setCellValue('B' . $fila, $row['nombre_tecnico']);
    $sheet->setCellValue('C' . $fila, $row['dni_tecnico']);
    $sheet->setCellValue('D' . $fila, $row['edad_tecnico']);
    $sheet->setCellValue('E' . $fila, $row['num_telef']);
    $fila++;
}

// Aplicar bordes a la tabla
$ultimaFila = $fila - 1;
$sheet->getStyle("A1:E$ultimaFila")->applyFromArray([
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
header('Content-Disposition: attachment; filename="reporte_tecnico.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
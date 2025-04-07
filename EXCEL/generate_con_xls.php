<?php
require '../vendor/autoload.php';
require '../conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Recibir parámetros de filtrado
$filter_empresa = $_POST['filter_empresa'] ?? '';
$filter_estado = $_POST['filter_estado'] ?? '';
$filter_utilidad = $_POST['filter_utilidad'] ?? '';
$filter_usuario = $_POST['filter_usuario'] ?? '';
$filter_search = $_POST['filter_search'] ?? '';

// Crear una nueva hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir encabezados y estilos
$encabezados = ['ID', 'Nombre', 'Cantidad', 'Empresa', 'Estado', 'Utilidad', 'Ingreso', 'Usuario'];
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

// Construir la consulta SQL con los filtros
$sql = "SELECT c.id_consumibles, c.nombre_consumibles, c.cantidad_consumibles, 
        e.nombre AS nombre_empresa, es.nombre_estado, u.nombre_utilidad, c.fecha_ingreso, us.nombre AS nombre_usuario
        FROM tbl_consumibles c
        LEFT JOIN tbl_empresa e ON c.id_empresa = e.id_empresa
        LEFT JOIN tbl_estados es ON c.estado_consumibles = es.id_estado
        LEFT JOIN tbl_utilidad u ON c.utilidad_consumibles = u.id_utilidad
        LEFT JOIN tbl_users us ON c.id_user = us.id_user
        WHERE 1=1 AND c.id_status = 1";

// Aplicar filtros
if (!empty($filter_empresa)) {
    $sql .= " AND e.nombre = '" . $conn->real_escape_string($filter_empresa) . "'";
}
if (!empty($filter_estado)) {
    $sql .= " AND es.nombre_estado = '" . $conn->real_escape_string($filter_estado) . "'";
}
if (!empty($filter_utilidad)) {
    $sql .= " AND u.nombre_utilidad = '" . $conn->real_escape_string($filter_utilidad) . "'";
}
if (!empty($filter_usuario)) {
    $sql .= " AND us.nombre = '" . $conn->real_escape_string($filter_usuario) . "'";
}
if (!empty($filter_search)) {
    $sql .= " AND c.nombre_consumibles LIKE '%" . $conn->real_escape_string($filter_search) . "%'";
}
$sql .= " ORDER BY c.id_consumibles";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$fila = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['id_consumibles']);
    $sheet->setCellValue('B' . $fila, $row['nombre_consumibles']);
    $sheet->setCellValue('C' . $fila, $row['cantidad_consumibles']);
    $sheet->setCellValue('D' . $fila, $row['nombre_empresa']);
    $sheet->setCellValue('E' . $fila, $row['nombre_estado']);
    $sheet->setCellValue('F' . $fila, $row['nombre_utilidad']);
    $sheet->setCellValue('G' . $fila, $row['fecha_ingreso']);
    $sheet->setCellValue('H' . $fila, $row['nombre_usuario']);
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
header('Content-Disposition: attachment; filename="reporte_consumibles.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
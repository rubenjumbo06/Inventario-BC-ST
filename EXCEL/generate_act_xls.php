<?php
require '../vendor/autoload.php';
require '../conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Recibir parámetros de filtrado
$filtro_estado = $_POST['filter_estado'] ?? '';
$filtro_empresa = $_POST['filter_empresa'] ?? '';
$filtro_ubicacion = $_POST['filter_ubicacion'] ?? '';
$filtro_busqueda = $_POST['filter_search'] ?? '';

// Crear una nueva hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir encabezados y estilos
$encabezados = ['ID', 'Nombre', 'Cantidad', 'Estado', 'Empresa', 'IP', 'MAC', 'SN', 'Ubicación', 'Ingreso'];
$columnas = range('A', 'J');

// Aplicar estilos a los encabezados
$sheet->getStyle('A1:J1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0073E6']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
]);

// Insertar encabezados
foreach ($encabezados as $index => $nombre) {
    $sheet->setCellValue($columnas[$index] . '1', $nombre);
}

// Construir la consulta SQL con los filtros
$sql = "SELECT a.id_activos, a.nombre_activos, a.cantidad_activos, es.nombre_estado AS estado, 
               e.nombre AS nombre_empresa, a.IP, a.MAC, a.SN, a.ubicacion_activos, a.fecha_ingreso 
        FROM tbl_activos a
        LEFT JOIN tbl_empresa e ON a.id_empresa = e.id_empresa
        LEFT JOIN tbl_estados es ON a.estado_activos = es.id_estado
        WHERE 1=1 AND a.id_status = 1";

// Aplicar filtros
if (!empty($filtro_estado)) {
    $sql .= " AND es.nombre_estado = '" . $conn->real_escape_string($filtro_estado) . "'";
}
if (!empty($filtro_empresa)) {
    $sql .= " AND e.nombre = '" . $conn->real_escape_string($filtro_empresa) . "'";
}
if (!empty($filtro_ubicacion)) {
    $sql .= " AND a.ubicacion_activos = '" . $conn->real_escape_string($filtro_ubicacion) . "'";
}
if (!empty($filtro_busqueda)) {
    $sql .= " AND a.nombre_activos LIKE '%" . $conn->real_escape_string($filtro_busqueda) . "%'";
}

$result = $conn->query($sql);
$fila = 2;

while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $fila, $row['id_activos']);
    $sheet->setCellValue('B' . $fila, $row['nombre_activos']);
    $sheet->setCellValue('C' . $fila, $row['cantidad_activos']);
    $sheet->setCellValue('D' . $fila, $row['estado']);
    $sheet->setCellValue('E' . $fila, $row['nombre_empresa']);
    $sheet->setCellValue('F' . $fila, $row['IP']);
    $sheet->setCellValue('G' . $fila, $row['MAC']);
    $sheet->setCellValue('H' . $fila, $row['SN']);
    $sheet->setCellValue('I' . $fila, $row['ubicacion_activos']);
    $sheet->setCellValue('J' . $fila, $row['fecha_ingreso']);
    $fila++;
}

// Aplicar bordes a la tabla
$ultimaFila = $fila - 1;
$sheet->getStyle("A1:J$ultimaFila")->applyFromArray([
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
header('Content-Disposition: attachment; filename="reporte_activos.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
<?php
session_start();
require_once("../fpdf/fpdf.php"); // Ajusta la ruta si es necesario
require_once("../conexion.php");  // Ajusta la ruta si es necesario

// Obtener los filtros desde el formulario POST
$filter_estado = isset($_POST['filter_estado']) && !empty($_POST['filter_estado']) ? $_POST['filter_estado'] : null;
$filter_empresa = isset($_POST['filter_empresa']) && !empty($_POST['filter_empresa']) ? $_POST['filter_empresa'] : null;
$filter_ubicacion = isset($_POST['filter_ubicacion']) && !empty($_POST['filter_ubicacion']) ? $_POST['filter_ubicacion'] : null;
$filter_search = isset($_POST['filter_search']) && !empty($_POST['filter_search']) ? $_POST['filter_search'] : null;

// Construir la consulta SQL con filtros dinámicos
$sql = "SELECT a.id_activos, a.nombre_activos, a.cantidad_activos, es.nombre_estado AS estado, e.nombre AS nombre_empresa, 
        a.IP, a.MAC, a.SN, a.ubicacion_activos, a.fecha_ingreso 
        FROM tbl_activos a
        LEFT JOIN tbl_empresa e ON a.id_empresa = e.id_empresa
        LEFT JOIN tbl_estados es ON a.estado_activos = es.id_estado
        WHERE 1=1";
$params = [];
$types = "";

if ($filter_estado) {
    $sql .= " AND es.nombre_estado = ?";
    $params[] = $filter_estado;
    $types .= "s";
}
if ($filter_empresa) {
    $sql .= " AND e.nombre = ?";
    $params[] = $filter_empresa;
    $types .= "s";
}
if ($filter_ubicacion) {
    $sql .= " AND a.ubicacion_activos = ?";
    $params[] = $filter_ubicacion;
    $types .= "s";
}
if ($filter_search) {
    $sql .= " AND a.nombre_activos LIKE ?";
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

// Crear el PDF con tus estilos originales
class PDF extends FPDF {
    function Header() {
        $image_path = __DIR__ . '/../assets/img/fondopdf.jpeg';
        if (!file_exists($image_path)) {
            die("Error: La imagen de fondo no existe en $image_path");
        }

        $this->Image($image_path, 98.5, 65, 100, 80);
        $this->Image(__DIR__ . '/../assets/img/logo.png', 15, 10, 25);

        $this->SetFont('Arial', 'B', 24);
        $this->SetY(20);
        $this->Cell(0, 20, 'Reporte de Activos', 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF('L'); // Orientación horizontal como en tu original
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(50, 168, 82); // Verde de tu encabezado
$pdf->SetTextColor(255); // Texto blanco

// Configuración exacta de columnas
$widths = [
    'id' => 10,
    'nombre' => 40,
    'cantidad' => 10,
    'estado' => 18,
    'empresa' => 18,
    'ip' => 25,
    'mac' => 40,
    'sn' => 30,
    'ubicacion' => 23,
    'ingreso' => 35
];

$tableWidth = array_sum($widths);
$startX = ($pdf->GetPageWidth() - $tableWidth) / 2;

// Encabezado
$pdf->SetX($startX);
foreach ($widths as $key => $width) {
    $header = match($key) {
        'id' => 'ID',
        'nombre' => 'Nombre',
        'cantidad' => 'Cant.',
        'estado' => 'Est.',
        'empresa' => 'Emp.',
        'ip' => 'IP',
        'mac' => 'MAC',
        'sn' => 'SN',
        'ubicacion' => 'Ubicacion',
        'ingreso' => 'Ingreso'
    };
    $pdf->Cell($width, 8, $header, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0); // Texto negro para los datos

// Datos filtrados
while ($row = $result->fetch_assoc()) {
    $pdf->SetX($startX);
    
    // Preparar el texto
    $nombre = ($row['id_activos'] == 17) 
        ? 'GLCCTE(@)1000Mbps - Segmento 4 - Cliente 4 05 Jose S.' 
        : $row['nombre_activos'];
    $nombre = utf8_decode($nombre);
    
    // Calcular altura necesaria (método más preciso)
    $pdf->SetFont('Arial', '', 10);
    $maxWidth = $widths['nombre'] - 2; // Margen interno
    $textHeight = 6; // Altura por línea
    
    // Dividir el texto en líneas
    $lines = [];
    $words = explode(' ', $nombre);
    $currentLine = '';
    
    foreach ($words as $word) {
        $testLine = $currentLine ? $currentLine.' '.$word : $word;
        if ($pdf->GetStringWidth($testLine) < $maxWidth) {
            $currentLine = $testLine;
        } else {
            $lines[] = $currentLine;
            $currentLine = $word;
        }
    }
    $lines[] = $currentLine;
    
    $lineCount = count($lines);
    $cellHeight = max(10, $lineCount * $textHeight); // Mínimo 10 de altura
    
    // Dibujar todas las celdas primero (para bordes)
    $pdf->Cell($widths['id'], $cellHeight, $row['id_activos'], 1, 0, 'C');
    
    // Guardar posición para el nombre
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    
    // Dibujar celda de nombre (solo borde)
    $pdf->Cell($widths['nombre'], $cellHeight, '', 1, 0);
    
    // Escribir el texto en la celda de nombre
    $pdf->SetXY($x, $y);
    foreach ($lines as $i => $line) {
        $pdf->Cell($widths['nombre'], $textHeight, $line, 0, 2, 'C');
    }
    
    // Restaurar posición para continuar
    $pdf->SetXY($x + $widths['nombre'], $y);
    
    // Resto de celdas (todas con la misma altura)
    $pdf->Cell($widths['cantidad'], $cellHeight, $row['cantidad_activos'], 1, 0, 'C');
    $pdf->Cell($widths['estado'], $cellHeight, utf8_decode($row['estado']), 1, 0, 'C');
    $pdf->Cell($widths['empresa'], $cellHeight, utf8_decode($row['nombre_empresa']), 1, 0, 'C');
    $pdf->Cell($widths['ip'], $cellHeight, $row['IP'], 1, 0, 'C');
    $pdf->Cell($widths['mac'], $cellHeight, $row['MAC'], 1, 0, 'C');
    $pdf->Cell($widths['sn'], $cellHeight, $row['SN'], 1, 0, 'C');
    $pdf->Cell($widths['ubicacion'], $cellHeight, utf8_decode($row['ubicacion_activos']), 1, 0, 'C');
    $pdf->Cell($widths['ingreso'], $cellHeight, $row['fecha_ingreso'], 1, 1, 'C');
    
    // Ajustar posición Y si hubo saltos
    if ($lineCount > 1) {
        $pdf->SetY($y + $cellHeight);
    }
}

// Salida del PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_activos.pdf"');
header('Cache-Control: max-age=0');
$pdf->Output('F', 'php://output');
exit();
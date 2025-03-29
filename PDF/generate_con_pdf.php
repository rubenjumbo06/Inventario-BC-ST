<?php
session_start(); 
require('../fpdf/fpdf.php');
require('../conexion.php');

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
        $this->Cell(0, 20, utf8_decode('Reporte de Consumibles'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }
}

// Función específica solo para comillas
function fixQuotes($text) {
    // Solo reemplazar comillas "elegantes" por comillas simples y dobles estándar
    $replacements = [
        '“' => '"', // Comilla doble izquierda
        '”' => '"', // Comilla doble derecha
        '‘' => "'", // Comilla simple izquierda
        '’' => "'"  // Comilla simple derecha
    ];
    return strtr($text, $replacements);
}

$pdf = new PDF('L');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(50, 168, 82);
$pdf->SetTextColor(255);

// Configurar conexión a MySQL con UTF-8
$conn->set_charset("utf8mb4");

$widths = [
    'id' => 15,
    'nombre' => 50,
    'cantidad' => 25,
    'empresa' => 20,
    'estado' => 20,
    'utilidad' => 25,
    'ingreso' => 60,
    'usuario' => 25
];

$tableWidth = array_sum($widths);
$startX = ($pdf->GetPageWidth() - $tableWidth) / 2;

// Encabezados
$pdf->SetX($startX);
$pdf->Cell($widths['id'], 10, 'ID', 1, 0, 'C', true);
$pdf->Cell($widths['nombre'], 10, utf8_decode('Nombre'), 1, 0, 'C', true);
$pdf->Cell($widths['cantidad'], 10, utf8_decode('Cant.'), 1, 0, 'C', true);
$pdf->Cell($widths['empresa'], 10, utf8_decode('Emp.'), 1, 0, 'C', true);
$pdf->Cell($widths['estado'], 10, utf8_decode('Est.'), 1, 0, 'C', true);
$pdf->Cell($widths['utilidad'], 10, utf8_decode('Uti.'), 1, 0, 'C', true);
$pdf->Cell($widths['ingreso'], 10, utf8_decode('Ingreso'), 1, 0, 'C', true);
$pdf->Cell($widths['usuario'], 10, utf8_decode('Usuario'), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

$sql = "SELECT * FROM tbl_consumibles ORDER BY id_consumibles";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $pdf->SetX($startX);
    
    // Procesar solo las comillas, dejando intactos otros caracteres
    $nombre = fixQuotes($row['nombre_consumibles']);
    $nombre = utf8_decode($nombre); // Solo esto para FPDF
    
    // Dividir texto en líneas si es necesario
    $maxWidth = $widths['nombre'] - 2;
    $textHeight = 6;
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
    $cellHeight = max(10, $lineCount * $textHeight);
    
    // Dibujar celdas
    $pdf->Cell($widths['id'], $cellHeight, $row['id_consumibles'], 1, 0, 'C');
    
    // Celda de nombre con texto multilínea
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Cell($widths['nombre'], $cellHeight, '', 1, 0);
    
    // Escribir texto línea por línea
    $pdf->SetXY($x, $y);
    foreach ($lines as $line) {
        $pdf->Cell($widths['nombre'], $textHeight, $line, 0, 2, 'C');
    }
    $pdf->SetXY($x + $widths['nombre'], $y);
    
    // Resto de celdas
    $pdf->Cell($widths['cantidad'], $cellHeight, $row['cantidad_consumibles'], 1, 0, 'C');
    $pdf->Cell($widths['empresa'], $cellHeight, $row['id_empresa'], 1, 0, 'C');
    $pdf->Cell($widths['estado'], $cellHeight, $row['estado_consumibles'], 1, 0, 'C');
    $pdf->Cell($widths['utilidad'], $cellHeight, $row['utilidad_consumibles'], 1, 0, 'C');
    $pdf->Cell($widths['ingreso'], $cellHeight, $row['fecha_ingreso'], 1, 0, 'C');
    $pdf->Cell($widths['usuario'], $cellHeight, $row['id_user'], 1, 1, 'C');
    
    if ($lineCount > 1) {
        $pdf->SetY($y + $cellHeight);
    }
}

// Configuración de salida
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_consumibles.pdf"');
header('Cache-Control: max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

$pdf->Output('D', 'reporte_consumibles.pdf');
exit();
?>
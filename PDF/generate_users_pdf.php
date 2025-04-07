<?php
session_start();
require_once("../fpdf/fpdf.php"); // Ajusta la ruta según tu estructura
require_once("../conexion.php");  // Ajusta la ruta según tu estructura

// Obtener los filtros desde el formulario POST
$filter_search = isset($_POST['filter_search']) && !empty($_POST['filter_search']) ? $_POST['filter_search'] : null;
$filter_rol = isset($_POST['filter_rol']) && !empty($_POST['filter_rol']) ? $_POST['filter_rol'] : null;

// Construir la consulta SQL con filtros dinámicos y condición id_status = 1
$sql = "SELECT id_user, nombre, apellidos, username, role, correo, telefono, fecha_creacion, fecha_modificacion 
        FROM tbl_users 
        WHERE id_status = 1"; // Condición fija para usuarios activos
$params = [];
$types = "";

if ($filter_search) {
    $sql .= " AND nombre LIKE ?";
    $params[] = "%$filter_search%";
    $types .= "s";
}
if ($filter_rol) {
    $sql .= " AND role = ?";
    $params[] = $filter_rol;
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
        $this->Cell(0, 20, 'Reporte de Usuarios', 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
    
    function wrapText($text, $maxWidth) {
        $lines = [];
        $words = explode(' ', $text);
        $currentLine = '';
        
        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine.' '.$word : $word;
            if ($this->GetStringWidth($testLine) < $maxWidth) {
                $currentLine = $testLine;
            } else {
                $lines[] = $currentLine;
                $currentLine = $word;
            }
        }
        $lines[] = $currentLine;
        return $lines;
    }
}

$pdf = new PDF('L');
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(50, 168, 82);
$pdf->SetTextColor(255);

// Configuración exacta de columnas
$widths = [
    'id' => 15,
    'nombre' => 30,
    'apellidos' => 25,
    'usuario' => 25,
    'rol' => 15,
    'correo' => 55,
    'telefono' => 25,
    'creacion' => 40,
    'modificacion' => 40
];

$tableWidth = array_sum($widths);
$pageWidth = $pdf->GetPageWidth();
$startX = ($pageWidth - $tableWidth) / 2;

// Encabezado
$pdf->SetX($startX);
foreach ($widths as $key => $width) {
    $header = match($key) {
        'id' => 'ID',
        'nombre' => 'Nombre',
        'apellidos' => 'Apellidos',
        'usuario' => 'Usuario',
        'rol' => 'Rol',
        'correo' => 'Correo',
        'telefono' => 'Telefono',
        'creacion' => 'Creacion',
        'modificacion' => 'Modificacion'
    };
    $pdf->Cell($width, 10, $header, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

// Datos filtrados
while ($row = $result->fetch_assoc()) {
    $pdf->SetX($startX);
    
    // Calcular altura necesaria para nombre y apellidos
    $textHeight = 6; // Altura por línea
    $maxHeight = 10; // Altura mínima
    
    // Procesar nombre
    $nombre = utf8_decode($row['nombre']);
    $maxWidth = $widths['nombre'] - 2;
    $nombreLines = $pdf->wrapText($nombre, $maxWidth);
    $nombreHeight = max($maxHeight, count($nombreLines) * $textHeight);
    
    // Procesar apellidos
    $apellidos = utf8_decode($row['apellidos']);
    $maxWidth = $widths['apellidos'] - 2;
    $apellidosLines = $pdf->wrapText($apellidos, $maxWidth);
    $apellidosHeight = max($maxHeight, count($apellidosLines) * $textHeight);
    
    // Altura final de la fila
    $cellHeight = max($nombreHeight, $apellidosHeight);
    
    // Dibujar ID
    $pdf->Cell($widths['id'], $cellHeight, $row['id_user'], 1, 0, 'C');
    
    // Dibujar Nombre
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->Cell($widths['nombre'], $cellHeight, '', 1, 0);
    $pdf->SetXY($x, $y);
    foreach ($nombreLines as $i => $line) {
        $pdf->Cell($widths['nombre'], $textHeight, $line, 0, 2, 'C');
    }
    $pdf->SetXY($x + $widths['nombre'], $y);
    
    // Dibujar Apellidos
    $x = $pdf->GetX();
    $pdf->Cell($widths['apellidos'], $cellHeight, '', 1, 0);
    $pdf->SetXY($x, $y);
    foreach ($apellidosLines as $i => $line) {
        $pdf->Cell($widths['apellidos'], $textHeight, $line, 0, 2, 'C');
    }
    $pdf->SetXY($x + $widths['apellidos'], $y);
    
    // Resto de celdas (altura uniforme)
    $pdf->Cell($widths['usuario'], $cellHeight, $row['username'], 1, 0, 'C');
    $pdf->Cell($widths['rol'], $cellHeight, $row['role'], 1, 0, 'C');
    $pdf->Cell($widths['correo'], $cellHeight, $row['correo'], 1, 0, 'C');
    $pdf->Cell($widths['telefono'], $cellHeight, $row['telefono'], 1, 0, 'C');
    $pdf->Cell($widths['creacion'], $cellHeight, $row['fecha_creacion'], 1, 0, 'C');
    $pdf->Cell($widths['modificacion'], $cellHeight, $row['fecha_modificacion'], 1, 1, 'C');
    
    // Ajustar posición Y si hubo saltos
    if ($cellHeight > 10) {
        $pdf->SetY($y + $cellHeight);
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_usuarios.pdf"');
header('Cache-Control: max-age=0');
$pdf->Output('F', 'php://output');
exit();
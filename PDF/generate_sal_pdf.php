<?php
session_start();
require_once("../fpdf/fpdf.php");
require_once("../conexion.php");

// Obtener los filtros desde el formulario POST
$filter_titulo = isset($_POST['titulo']) && !empty($_POST['titulo']) ? $_POST['titulo'] : null;
$filter_destino = isset($_POST['destino']) && !empty($_POST['destino']) ? $_POST['destino'] : null;
$filter_fecha_desde = isset($_POST['fechaDesde']) && !empty($_POST['fechaDesde']) ? $_POST['fechaDesde'] : null;
$filter_fecha_hasta = isset($_POST['fechaHasta']) && !empty($_POST['fechaHasta']) ? $_POST['fechaHasta'] : null;
$filter_usuario = isset($_POST['usuario']) && !empty($_POST['usuario']) ? $_POST['usuario'] : null;

// Construir la consulta SQL con filtros dinámicos
$sql = "SELECT s.id_salidas, s.fecha_creacion, s.items, s.titulo, s.Destino, s.body, u.username 
        FROM tbl_reg_salidas s
        LEFT JOIN tbl_users u ON s.id_user = u.id_user
        WHERE 1=1";
$params = [];
$types = "";

if ($filter_titulo) {
    $sql .= " AND s.titulo LIKE ?";
    $params[] = "%$filter_titulo%";
    $types .= "s";
}
if ($filter_destino) {
    $sql .= " AND s.Destino LIKE ?";
    $params[] = "%$filter_destino%";
    $types .= "s";
}
if ($filter_fecha_desde) {
    $sql .= " AND s.fecha_creacion >= ?";
    $params[] = "$filter_fecha_desde 00:00:00";
    $types .= "s";
}
if ($filter_fecha_hasta) {
    $sql .= " AND s.fecha_creacion <= ?";
    $params[] = "$filter_fecha_hasta 23:59:59";
    $types .= "s";
}
if ($filter_usuario) {
    $sql .= " AND u.username = ?";
    $params[] = $filter_usuario;
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
        $this->Cell(0, 20, 'Reporte de Salidas', 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
    
    function writeWrappedRow($data, $widths, $startX, $lineHeight = 6) {
        $this->SetX($startX);
        
        $heights = [];
        foreach ($data as $key => $text) {
            if (in_array($key, ['titulo', 'destino', 'cuerpo'])) {
                $wrapped = $this->wrapText(utf8_decode($text), $widths[$key] - 2);
                $heights[$key] = count($wrapped) * $lineHeight;
            } else {
                $heights[$key] = $lineHeight;
            }
        }
        
        $maxHeight = max(array_values($heights));
        $maxHeight = max($maxHeight, 10);
        
        foreach ($widths as $key => $width) {
            if (in_array($key, ['titulo', 'destino', 'cuerpo'])) {
                $x = $this->GetX();
                $y = $this->GetY();
                
                $this->Cell($width, $maxHeight, '', 1, 0);
                
                $this->SetXY($x, $y + ($maxHeight - $heights[$key]) / 2);
                
                $wrapped = $this->wrapText(utf8_decode($data[$key]), $width - 2);
                foreach ($wrapped as $line) {
                    $this->Cell($width, $lineHeight, $line, 0, 2, 'C');
                }
                
                $this->SetXY($x + $width, $y);
            } else {
                $x = $this->GetX();
                $y = $this->GetY();
                
                $this->Cell($width, $maxHeight, '', 1, 0);
                $this->SetXY($x, $y + ($maxHeight - $lineHeight) / 2);
                $this->Cell($width, $lineHeight, utf8_decode($data[$key]), 0, 0, 'C');
                $this->SetXY($x + $width, $y);
            }
        }
        $this->Ln();
        $this->SetY($this->GetY() + $maxHeight - $lineHeight);
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

$widths = [
    'id' => 10,
    'fecha' => 40,
    'items' => 15,
    'titulo' => 50,
    'destino' => 35,
    'cuerpo' => 85,
    'usuario' => 25
];

$tableWidth = array_sum($widths);
$startX = ($pdf->GetPageWidth() - $tableWidth) / 2;

$pdf->SetX($startX);
foreach ($widths as $key => $width) {
    $header = match($key) {
        'id' => 'ID',
        'fecha' => 'Fecha',
        'items' => 'Items',
        'titulo' => 'Titulo',
        'destino' => 'Destino',
        'cuerpo' => 'Cuerpo',
        'usuario' => 'Usuario'
    };
    $pdf->Cell($width, 8, $header, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

while ($row = $result->fetch_assoc()) {
    $data = [
        'id' => $row['id_salidas'],
        'fecha' => $row['fecha_creacion'],
        'items' => $row['items'],
        'titulo' => $row['titulo'],
        'destino' => $row['Destino'],
        'cuerpo' => $row['body'],
        'usuario' => $row['username']
    ];
    
    $pdf->writeWrappedRow($data, $widths, $startX);
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_salidas.pdf"');
header('Cache-Control: max-age=0');
$pdf->Output('D', 'reporte_salidas.pdf');
exit();
?>
<?php
session_start();
require_once("../fpdf/fpdf.php");
require_once("../conexion.php");

// Obtener el filtro de búsqueda desde el formulario POST
$filter_search = isset($_POST['filter_search']) && !empty($_POST['filter_search']) ? $_POST['filter_search'] : null;

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
        $this->Cell(0, 20, 'Reporte de Utilidad', 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Construir la consulta SQL
$sql = "SELECT id_utilidad, nombre_utilidad, descripcion 
        FROM tbl_utilidad 
        WHERE id_status = 1";
$params = [];
$types = "";

if ($filter_search) {
    $sql .= " AND (nombre_utilidad LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
    $types .= "ss";
}

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
if (!empty($params)) {
    if (!$stmt->bind_param($types, ...$params)) {
        die("Error al vincular parámetros: " . $stmt->error);
    }
}
if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}
$result = $stmt->get_result();

// Verificar si hay datos
$row_count = $result->num_rows;
if ($row_count == 0) {
    die("No se encontraron utilidades con id_status = 1");
}

// Crear el PDF
$pdf = new PDF('L');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(50, 168, 82);
$pdf->SetTextColor(255);

// Configuración de columnas
$widths = [
    'id' => 15,
    'nombre' => 70,
    'descripcion' => 150
];
$tableWidth = array_sum($widths);
$startX = ($pdf->GetPageWidth() - $tableWidth) / 2;

// Encabezado
$pdf->SetX($startX);
$pdf->Cell($widths['id'], 10, 'ID', 1, 0, 'C', true);
$pdf->Cell($widths['nombre'], 10, 'Nombre', 1, 0, 'C', true);
$pdf->Cell($widths['descripcion'], 10, 'Descripcion', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

// Generar filas
while ($row = $result->fetch_assoc()) {
    $pdf->SetX($startX);
    $pdf->Cell($widths['id'], 10, $row['id_utilidad'], 1, 0, 'C');
    $pdf->Cell($widths['nombre'], 10, utf8_decode($row['nombre_utilidad']), 1, 0, 'C');
    $pdf->Cell($widths['descripcion'], 10, utf8_decode($row['descripcion']), 1, 1, 'C');
}

// Cerrar statement y conexión
$stmt->close();
$conn->close();

// Enviar el PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_utilidades.pdf"');
header('Cache-Control: max-age=0');
$pdf->Output('F', 'php://output');
exit();
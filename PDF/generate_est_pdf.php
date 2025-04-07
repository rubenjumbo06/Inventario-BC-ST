<?php
session_start();
require_once("../fpdf/fpdf.php");
require_once("../conexion.php");

// Verificar si la conexión es válida
if (!$conn) {
    die("Error: No se pudo conectar a la base de datos.");
}

// Obtener el filtro de búsqueda desde el formulario POST
$filter_search = isset($_POST['filter_search']) && !empty($_POST['filter_search']) ? $_POST['filter_search'] : null;

// Construir la consulta SQL
$sql = "SELECT id_estado, nombre_estado, descripcion FROM tbl_estados WHERE id_status =1";
$params = [];
$types = "";

if ($filter_search) {
    $sql .= " AND nombre_estado LIKE ?";
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
if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}
$result = $stmt->get_result();

// Crear el PDF
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
        $this->Cell(0, 20, 'Reporte de Estados', 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetX($this->GetPageWidth() / 2 - 10);
        $this->Cell(20, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Limpiar buffer para evitar salida previa
ob_clean();

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
$pdf->SetX($startX);

// Encabezado
$pdf->Cell($widths['id'], 10, 'ID', 1, 0, 'C', true);
$pdf->Cell($widths['nombre'], 10, 'Nombre', 1, 0, 'C', true);
$pdf->Cell($widths['descripcion'], 10, 'Descripcion', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

// Datos filtrados
$row_count = 0;
while ($row = $result->fetch_assoc()) {
    $row_count++;
    $pdf->SetX($startX);
    $pdf->Cell($widths['id'], 10, $row['id_estado'], 1, 0, 'C');
    $pdf->Cell($widths['nombre'], 10, utf8_decode($row['nombre_estado']), 1, 0, 'C');
    $pdf->Cell($widths['descripcion'], 10, utf8_decode($row['descripcion']), 1, 1, 'C');
}

// Si no hay datos
if ($row_count == 0) {
    $pdf->SetX($startX);
    $pdf->Cell($tableWidth, 10, 'No se encontraron estados.', 1, 1, 'C');
}

// Cerrar statement y conexión
$stmt->close();
$conn->close();

// Enviar encabezados y forzar descarga
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_estados.pdf"');
header('Cache-Control: max-age=0');
$pdf->Output('D', 'reporte_estados.pdf');
exit();
<?php
session_start();
require_once("../fpdf/fpdf.php"); // Ajusta la ruta según tu estructura
require_once("../conexion.php");  // Ajusta la ruta según tu estructura

// Obtener el filtro de búsqueda desde el formulario POST
$filter_search = isset($_POST['filter_search']) && !empty($_POST['filter_search']) ? $_POST['filter_search'] : null;

// Construir la consulta SQL con filtro dinámico
$sql = "SELECT id_estado, nombre_estado, descripcion FROM tbl_estados WHERE 1=1";
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
$stmt->execute();
$result = $stmt->get_result();

// Crear el PDF con tus estilos originales
class PDF extends FPDF {
    function Header() {
        $image_path = __DIR__ . '/../assets/img/fondopdf.jpeg';
        if (!file_exists($image_path)) {
            die("Error: La imagen de fondo no existe en $image_path");
        }

        $this->Image($image_path, 98.5, 65, 100, 80); // Centrar fondo
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

$pdf = new PDF('L'); // Orientación horizontal
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(50, 168, 82); // Verde de tu encabezado
$pdf->SetTextColor(255); // Texto blanco

// Ancho total de la tabla (ajustado a tbl_estados)
$tableWidth = 15 + 70 + 150; // ID, Nombre, Descripción
$pageWidth = $pdf->GetPageWidth();
$startX = ($pageWidth - $tableWidth) / 2;
$pdf->SetX($startX);

// Encabezado
$pdf->Cell(15, 10, 'ID', 1, 0, 'C', true);
$pdf->Cell(70, 10, 'Nombre', 1, 0, 'C', true);
$pdf->Cell(150, 10, 'Descripcion', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0); // Texto negro para los datos

// Datos filtrados
while ($row = $result->fetch_assoc()) {
    $pdf->SetX($startX);
    $pdf->Cell(15, 10, $row['id_estado'], 1, 0, 'C');
    $pdf->Cell(70, 10, utf8_decode($row['nombre_estado']), 1, 0, 'C');
    $pdf->Cell(150, 10, utf8_decode($row['descripcion']), 1, 1, 'C');
}

// Forzar la descarga del PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_estados.pdf"');
header('Cache-Control: max-age=0');

$pdf->Output('F', 'php://output'); // Enviar el PDF al navegador para descarga
exit();
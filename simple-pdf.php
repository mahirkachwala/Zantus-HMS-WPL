<?php
$tcpdfMain = __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
if (!class_exists('TCPDF', false) && file_exists($tcpdfMain)) {
	require_once $tcpdfMain;
}

if (!defined('PDF_PAGE_ORIENTATION')) {
	define('PDF_PAGE_ORIENTATION', 'P');
}
if (!defined('PDF_UNIT')) {
	define('PDF_UNIT', 'mm');
}
if (!defined('PDF_PAGE_FORMAT')) {
	define('PDF_PAGE_FORMAT', 'A4');
}

if (!class_exists('TCPDF')) {
	header('Content-Type: text/plain; charset=utf-8');
	echo 'TCPDF not available';
	exit();
}

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Zantus HMS');
$pdf->SetAuthor('Zantus HMS');
$pdf->SetTitle('Simple PDF Test');
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Zantus HMS PDF Test', 0, 1, 'C');
$pdf->Ln(4);
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 8, 'If you can see this PDF, TCPDF is working correctly on ByetHost.');
$pdf->Output('zantus-hms-test.pdf', 'I');
exit();
?>

<?php
$tcpdfBase = __DIR__ . '/vendor/tecnickcom/tcpdf/';
$tcpdfMain = __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';

if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
	define('K_TCPDF_EXTERNAL_CONFIG', true);
}
if (!defined('K_PATH_MAIN')) {
	define('K_PATH_MAIN', $tcpdfBase);
}
if (!defined('K_PATH_FONTS')) {
	define('K_PATH_FONTS', $tcpdfBase . 'fonts/');
}
if (!defined('K_PATH_CACHE')) {
	$cachePath = sys_get_temp_dir();
	if ($cachePath === '' || $cachePath === false) {
		$cachePath = __DIR__ . '/assets/';
	}
	$cachePath = rtrim(str_replace('\\', '/', $cachePath), '/') . '/';
	define('K_PATH_CACHE', $cachePath);
}

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
$pdf->SetMargins(14, 18, 14);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();
$helveticaBold = __DIR__ . '/vendor/tecnickcom/tcpdf/fonts/helveticab.php';
$helveticaRegular = __DIR__ . '/vendor/tecnickcom/tcpdf/fonts/helvetica.php';
$pdf->SetFillColor(245, 249, 255);
$pdf->Rect(0, 0, $pdf->getPageWidth(), 52, 'F');
$pdf->SetFillColor(37, 99, 235);
$pdf->RoundedRect(14, 12, 46, 24, 4, '1111', 'F');
$pdf->SetFillColor(52, 211, 153);
$pdf->Rect(14, 12, 8, 24, 'F');

$logoPath = __DIR__ . '/assets/images/zantus-logo.jpg';
if (file_exists($logoPath)) {
	$pdf->Image($logoPath, 25, 16, 12, 12, 'JPG', '', '', true, 300, '', false, false, 0, false, false, false);
}

$pdf->setFont('helvetica', 'B', 12, file_exists($helveticaBold) ? $helveticaBold : (file_exists($helveticaRegular) ? $helveticaRegular : ''));
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(39, 17);
$pdf->Cell(17, 5, 'Zantus', 0, 1, 'L', false, '', 0, false, 'T', 'M');
$pdf->setFont('helvetica', '', 7, file_exists($helveticaRegular) ? $helveticaRegular : '');
$pdf->SetXY(39, 22);
$pdf->Cell(17, 4, 'HMS', 0, 1, 'L', false, '', 0, false, 'T', 'M');

$pdf->SetTextColor(29, 78, 216);
$pdf->setFont('helvetica', 'B', 19, file_exists($helveticaBold) ? $helveticaBold : (file_exists($helveticaRegular) ? $helveticaRegular : ''));
$pdf->SetXY(72, 13);
$pdf->Cell(0, 9, 'PDF RENDER TEST', 0, 1, 'L', false, '', 0, false, 'T', 'M');

$pdf->SetTextColor(100, 116, 139);
$pdf->setFont('helvetica', '', 9, file_exists($helveticaRegular) ? $helveticaRegular : '');
$pdf->SetXY(72, 24);
$pdf->Cell(0, 5, 'TCPDF theme preview for Zantus HMS', 0, 1, 'L', false, '', 0, false, 'T', 'M');

$html = '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>'
	. '<td width="60%" style="border:1px solid #dbeafe;background-color:#ffffff;padding:12px;">'
	. '<div style="font-size:8px;color:#64748b;letter-spacing:0.6px;text-transform:uppercase;">Visual Check</div>'
	. '<div style="font-size:17px;color:#0f172a;font-weight:bold;padding-top:4px;">Zantus Themed PDF</div>'
	. '<div style="font-size:10px;color:#475569;padding-top:8px;">If this page opens properly, TCPDF fonts and layout rendering are now working on ByetHost.</div>'
	. '</td>'
	. '<td width="4%"></td>'
	. '<td width="36%" style="border:1px solid #a7f3d0;background-color:#ecfdf5;padding:12px;">'
	. '<div style="font-size:8px;color:#047857;text-transform:uppercase;">Status</div>'
	. '<div style="font-size:22px;color:#064e3b;font-weight:bold;padding-top:6px;">PASS</div>'
	. '<div style="font-size:9px;color:#065f46;padding-top:10px;">Theme + font pipeline ready</div>'
	. '</td>'
	. '</tr></table>';

$pdf->SetXY(14, 60);
$pdf->writeHTML($html, true, false, true, false, '');

$table = '<table cellpadding="6" cellspacing="0" border="0" width="100%">'
	. '<tr>'
	. '<td width="52%" style="background-color:#2563eb;color:#ffffff;font-size:9px;font-weight:bold;">Section</td>'
	. '<td width="16%" style="background-color:#2563eb;color:#ffffff;font-size:9px;font-weight:bold;">State</td>'
	. '<td width="32%" style="background-color:#2563eb;color:#ffffff;font-size:9px;font-weight:bold;">Note</td>'
	. '</tr>'
	. '<tr>'
	. '<td width="52%" style="border:1px solid #e2e8f0;background-color:#ffffff;">TCPDF Core</td>'
	. '<td width="16%" style="border:1px solid #e2e8f0;background-color:#ffffff;">Loaded</td>'
	. '<td width="32%" style="border:1px solid #e2e8f0;background-color:#ffffff;">Main library included</td>'
	. '</tr>'
	. '<tr>'
	. '<td width="52%" style="border:1px solid #e2e8f0;background-color:#f8fafc;">Helvetica Fonts</td>'
	. '<td width="16%" style="border:1px solid #e2e8f0;background-color:#f8fafc;">Required</td>'
	. '<td width="32%" style="border:1px solid #e2e8f0;background-color:#f8fafc;">Must exist in vendor/tecnickcom/tcpdf/fonts</td>'
	. '</tr>'
	. '<tr>'
	. '<td width="52%" style="border:1px solid #e2e8f0;background-color:#ffffff;">Receipt Theme</td>'
	. '<td width="16%" style="border:1px solid #e2e8f0;background-color:#ffffff;">Active</td>'
	. '<td width="32%" style="border:1px solid #e2e8f0;background-color:#ffffff;">Blue + teal Zantus styling</td>'
	. '</tr>'
	. '</table>';

$pdf->Ln(5);
$pdf->writeHTML($table, true, false, true, false, '');
$pdf->Ln(5);
$pdf->writeHTML('<div style="border:1px solid #dbeafe;background-color:#f8fbff;padding:10px 12px;"><div style="font-size:10px;font-weight:bold;color:#1d4ed8;margin-bottom:4px;">Next Step</div><div style="font-size:9px;line-height:1.7;color:#334155;">Open appointment, payment, and prescription receipt pages to preview the final layouts using live HMS data.</div></div>', true, false, true, false, '');

$pdf->SetFillColor(30, 64, 175);
$pdf->Rect(0, $pdf->getPageHeight() - 16, $pdf->getPageWidth(), 16, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->setFont('helvetica', '', 8, file_exists($helveticaRegular) ? $helveticaRegular : '');
$pdf->SetY(-12);
$pdf->Cell(0, 5, 'care@zantushms.com   |   www.zantushms.com', 0, 1, 'C');
$pdf->setFont('helvetica', 'B', 8, file_exists($helveticaBold) ? $helveticaBold : (file_exists($helveticaRegular) ? $helveticaRegular : ''));
$pdf->Cell(0, 4, 'Simple PDF Test', 0, 0, 'C');
$pdf->Output('zantus-hms-test.pdf', 'I');
exit();
?>

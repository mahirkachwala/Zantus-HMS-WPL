<?php
declare(strict_types=1);

@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

register_shutdown_function(static function () {
	$error = error_get_last();
	if ($error !== null) {
		echo "\nSHUTDOWN ERROR:\n";
		echo 'type=' . ($error['type'] ?? 'unknown') . "\n";
		echo 'message=' . ($error['message'] ?? 'unknown') . "\n";
		echo 'file=' . ($error['file'] ?? 'unknown') . "\n";
		echo 'line=' . ($error['line'] ?? 'unknown') . "\n";
	}
});

echo "STEP 1: PHP started\n";
echo 'PHP_VERSION=' . PHP_VERSION . "\n";

$baseDir = __DIR__;
$tcpdfBase = $baseDir . '/vendor/tecnickcom/tcpdf/';
$tcpdfMain = $tcpdfBase . 'tcpdf.php';
$fontRegular = $tcpdfBase . 'fonts/helvetica.php';
$fontBold = $tcpdfBase . 'fonts/helveticab.php';

echo "STEP 2: paths\n";
echo 'tcpdfMain=' . $tcpdfMain . "\n";
echo 'fontRegularExists=' . (file_exists($fontRegular) ? 'true' : 'false') . "\n";
echo 'fontBoldExists=' . (file_exists($fontBold) ? 'true' : 'false') . "\n";

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
	$cachePath = $baseDir . '/assets/pdf-cache';
	if (!is_dir($cachePath)) {
		@mkdir($cachePath, 0775, true);
	}
	if (!is_dir($cachePath) || !is_writable($cachePath)) {
		$cachePath = sys_get_temp_dir();
	}
	if ($cachePath === '' || $cachePath === false) {
		$cachePath = $baseDir . '/assets/';
	}
	$cachePath = rtrim(str_replace('\\', '/', $cachePath), '/') . '/';
	define('K_PATH_CACHE', $cachePath);
}
if (!defined('K_TCPDF_THROW_EXCEPTION_ERROR')) {
	define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
}

echo "STEP 3: constants set\n";
echo 'K_PATH_MAIN=' . K_PATH_MAIN . "\n";
echo 'K_PATH_FONTS=' . K_PATH_FONTS . "\n";
echo 'K_PATH_CACHE=' . K_PATH_CACHE . "\n";

require_once $tcpdfMain;
echo "STEP 4: TCPDF included\n";

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
echo "STEP 5: TCPDF object created\n";

$pdf->SetMargins(14, 18, 14);
$pdf->AddPage();
echo "STEP 6: page added\n";

$pdf->setFont('helvetica', '', 12, file_exists($fontRegular) ? $fontRegular : '');
echo "STEP 7: regular font applied\n";

$pdf->Cell(0, 10, 'TCPDF debug page loaded successfully.');
echo "STEP 8: text written\n";

$pdf->Output('tcpdf-step-debug.pdf', 'I');
echo "STEP 9: output sent\n";
exit();

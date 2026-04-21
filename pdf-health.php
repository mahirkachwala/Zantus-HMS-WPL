<?php
header('Content-Type: application/json; charset=utf-8');

$baseDir = __DIR__;
$tcpdfMain = $baseDir . '/vendor/tecnickcom/tcpdf/tcpdf.php';
$tcpdfAutoconfig = $baseDir . '/vendor/tecnickcom/tcpdf/tcpdf_autoconfig.php';
$tcpdfFonts = $baseDir . '/vendor/tecnickcom/tcpdf/fonts';
$tcpdfInclude = $baseDir . '/vendor/tecnickcom/tcpdf/include';

$status = [
	'php_version' => PHP_VERSION,
	'tcpdf_main' => file_exists($tcpdfMain),
	'tcpdf_autoconfig' => file_exists($tcpdfAutoconfig),
	'tcpdf_fonts_dir' => is_dir($tcpdfFonts),
	'tcpdf_include_dir' => is_dir($tcpdfInclude),
	'curl_enabled' => function_exists('curl_init'),
	'gd_enabled' => function_exists('gd_info'),
	'composer_platform_check_skipped' => true,
];
if (!class_exists('TCPDF', false) && $status['tcpdf_main']) {
	require_once $tcpdfMain;
}

$status['tcpdf_class_loaded'] = class_exists('TCPDF', false);
$status['pdf_page_orientation_defined'] = defined('PDF_PAGE_ORIENTATION');
$status['pdf_unit_defined'] = defined('PDF_UNIT');
$status['pdf_page_format_defined'] = defined('PDF_PAGE_FORMAT');

echo json_encode($status, JSON_PRETTY_PRINT);
exit();
?>

<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
require_once __DIR__ . '/include/checklogin.php';
check_login();
require_once __DIR__ . '/include/config.php';

$vendorAutoload = __DIR__ . '/vendor/autoload.php';
$tcpdfMain = __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
$tcpdfFonts = __DIR__ . '/vendor/tecnickcom/tcpdf/fonts';
$tcpdfInclude = __DIR__ . '/vendor/tecnickcom/tcpdf/include';
$logoPath = __DIR__ . '/assets/images/zantus-logo.jpg';

$status = [
	'vendor_autoload' => file_exists($vendorAutoload),
	'tcpdf_main' => file_exists($tcpdfMain),
	'tcpdf_fonts_dir' => is_dir($tcpdfFonts),
	'tcpdf_include_dir' => is_dir($tcpdfInclude),
	'logo_exists' => file_exists($logoPath),
];

if ($status['vendor_autoload']) {
	require_once $vendorAutoload;
}
if (!class_exists('TCPDF', false) && $status['tcpdf_main']) {
	require_once $tcpdfMain;
}

$status['tcpdf_class_loaded'] = class_exists('TCPDF', false);
$status['php_version'] = PHP_VERSION;
$status['curl_enabled'] = function_exists('curl_init');
$status['gd_enabled'] = function_exists('gd_info');

header('Content-Type: application/json; charset=utf-8');
echo json_encode($status, JSON_PRETTY_PRINT);
exit();
?>

<?php
header('Content-Type: application/json; charset=utf-8');

$baseDir = __DIR__;
$tcpdfBase = $baseDir . '/vendor/tecnickcom/tcpdf/';
$tcpdfMain = $baseDir . '/vendor/tecnickcom/tcpdf/tcpdf.php';
$tcpdfAutoconfig = $baseDir . '/vendor/tecnickcom/tcpdf/tcpdf_autoconfig.php';
$tcpdfFonts = $baseDir . '/vendor/tecnickcom/tcpdf/fonts';
$tcpdfInclude = $baseDir . '/vendor/tecnickcom/tcpdf/include';

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
		$cachePath = $baseDir . '/assets/';
	}
	$cachePath = rtrim(str_replace('\\', '/', $cachePath), '/') . '/';
	define('K_PATH_CACHE', $cachePath);
}

$status = [
	'php_version' => PHP_VERSION,
	'tcpdf_main' => file_exists($tcpdfMain),
	'tcpdf_autoconfig' => file_exists($tcpdfAutoconfig),
	'tcpdf_fonts_dir' => is_dir($tcpdfFonts),
	'tcpdf_include_dir' => is_dir($tcpdfInclude),
	'helvetica_php' => file_exists($tcpdfFonts . '/helvetica.php'),
	'helveticab_php' => file_exists($tcpdfFonts . '/helveticab.php'),
	'helveticai_php' => file_exists($tcpdfFonts . '/helveticai.php'),
	'helveticabi_php' => file_exists($tcpdfFonts . '/helveticabi.php'),
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
$status['k_path_fonts'] = defined('K_PATH_FONTS') ? K_PATH_FONTS : null;
$status['k_path_cache'] = defined('K_PATH_CACHE') ? K_PATH_CACHE : null;
$status['helvetica_include_test'] = false;

if ($status['helvetica_php']) {
	$type = null;
	$name = null;
	$desc = null;
	$up = null;
	$ut = null;
	$cw = null;
	try {
		include $tcpdfFonts . '/helvetica.php';
		$status['helvetica_include_test'] = isset($type, $name, $cw);
	} catch (\Throwable $e) {
		$status['helvetica_include_test'] = false;
		$status['helvetica_include_error'] = $e->getMessage();
	}
}

echo json_encode($status, JSON_PRETTY_PRINT);
exit();
?>

<?php
require_once __DIR__ . '/config.php';

$hmsTcpdfMain = dirname(__DIR__) . '/vendor/tecnickcom/tcpdf/tcpdf.php';
$hmsTcpdfBase = dirname(__DIR__) . '/vendor/tecnickcom/tcpdf/';

if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
	define('K_TCPDF_EXTERNAL_CONFIG', true);
}
if (!defined('K_PATH_MAIN')) {
	define('K_PATH_MAIN', $hmsTcpdfBase);
}
if (!defined('K_PATH_FONTS')) {
	define('K_PATH_FONTS', $hmsTcpdfBase . 'fonts/');
}
if (!defined('K_PATH_CACHE')) {
	$hmsCachePath = sys_get_temp_dir();
	if ($hmsCachePath === '' || $hmsCachePath === false) {
		$hmsCachePath = dirname(__DIR__) . '/assets/';
	}
	$hmsCachePath = rtrim(str_replace('\\', '/', $hmsCachePath), '/') . '/';
	define('K_PATH_CACHE', $hmsCachePath);
}

if (!class_exists('TCPDF', false) && file_exists($hmsTcpdfMain)) {
	require_once $hmsTcpdfMain;
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

if (!function_exists('hms_pdf_is_available')) {
	function hms_pdf_is_available() {
		return class_exists('TCPDF');
	}
}

if (hms_pdf_is_available() && !class_exists('HMSReceiptPDF')) {
	class HMSReceiptPDF extends TCPDF {
		public $hmsDocumentTitle = 'Zantus HMS Document';
		public $hmsSubtitle = 'Zantus Life Science Hospital';
		public $hmsLogoPath = '';
		public $hmsVariant = 'receipt';

		public function Header() {
			$pageWidth = $this->getPageWidth();

			if ($this->hmsVariant === 'prescription') {
				$this->SetFillColor(247, 250, 255);
				$this->Rect(0, 0, $pageWidth, 48, 'F');
				$this->SetFillColor(227, 238, 255);
				$this->Ellipse(28, 6, 24, 12, 0, 0, 360, 'F');
				$this->Ellipse(17, 43, 22, 10, 0, 0, 360, 'F');
				$this->SetDrawColor(191, 219, 254);
				$this->Line(14, 40, $pageWidth - 14, 40);

				if ($this->hmsLogoPath !== '' && file_exists($this->hmsLogoPath)) {
					$this->Image($this->hmsLogoPath, $pageWidth - 28, 8, 14, 14, 'JPG', '', '', true, 300, '', false, false, 0, false, false, false);
				}

				$this->SetTextColor(37, 99, 235);
				hms_pdf_apply_font($this, 'B', 18);
				$this->SetXY(15, 10);
				$this->Cell(0, 7, 'Zantus HMS', 0, 1, 'L', false, '', 0, false, 'T', 'M');

				$this->SetTextColor(71, 85, 105);
				hms_pdf_apply_font($this, '', 9);
				$this->SetX(15);
				$this->Cell(0, 5, $this->hmsSubtitle, 0, 1, 'L', false, '', 0, false, 'T', 'M');

				$this->SetTextColor(15, 23, 42);
				hms_pdf_apply_font($this, 'B', 15);
				$this->SetXY(15, 27);
				$this->Cell(0, 8, $this->hmsDocumentTitle, 0, 1, 'L', false, '', 0, false, 'T', 'M');
				return;
			}

			$this->SetFillColor(245, 249, 255);
			$this->Rect(0, 0, $pageWidth, 54, 'F');
			$this->SetFillColor(37, 99, 235);
			$this->RoundedRect(14, 12, 46, 24, 4, '1111', 'F');
			$this->SetFillColor(52, 211, 153);
			$this->Rect(14, 12, 8, 24, 'F');

			if ($this->hmsLogoPath !== '' && file_exists($this->hmsLogoPath)) {
				$this->Image($this->hmsLogoPath, 25, 16, 12, 12, 'JPG', '', '', true, 300, '', false, false, 0, false, false, false);
			}

			$this->SetTextColor(255, 255, 255);
			hms_pdf_apply_font($this, 'B', 12);
			$this->SetXY(39, 17);
			$this->Cell(17, 5, 'Zantus', 0, 1, 'L', false, '', 0, false, 'T', 'M');
			hms_pdf_apply_font($this, '', 7);
			$this->SetXY(39, 22);
			$this->Cell(17, 4, 'HMS', 0, 1, 'L', false, '', 0, false, 'T', 'M');

			$this->SetTextColor(29, 78, 216);
			hms_pdf_apply_font($this, 'B', 19);
			$this->SetXY(72, 13);
			$this->Cell(0, 9, strtoupper($this->hmsDocumentTitle), 0, 1, 'L', false, '', 0, false, 'T', 'M');

			$this->SetTextColor(100, 116, 139);
			hms_pdf_apply_font($this, '', 9);
			$this->SetXY(72, 24);
			$this->Cell(0, 5, $this->hmsSubtitle, 0, 1, 'L', false, '', 0, false, 'T', 'M');

			$this->SetFillColor(191, 219, 254);
			$this->Rect(72, 33, 24, 3, 'F');
			$this->SetDrawColor(203, 213, 225);
			$this->Line(14, 45, $pageWidth - 14, 45);
		}

		public function Footer() {
			$pageWidth = $this->getPageWidth();
			$pageHeight = $this->getPageHeight();

			if ($this->hmsVariant === 'prescription') {
				$this->SetDrawColor(191, 219, 254);
				$this->Line(15, $pageHeight - 18, $pageWidth - 15, $pageHeight - 18);
				$this->SetY(-15);
				$this->SetTextColor(71, 85, 105);
				hms_pdf_apply_font($this, '', 8);
				$this->Cell(0, 5, 'Zantus HMS | care@zantushms.com | www.zantushms.com | Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
				return;
			}

			$this->SetFillColor(30, 64, 175);
			$this->Rect(0, $pageHeight - 16, $pageWidth, 16, 'F');
			$this->SetTextColor(255, 255, 255);
			hms_pdf_apply_font($this, '', 8);
			$this->SetY(-12);
			$this->Cell(0, 5, 'care@zantushms.com   |   www.zantushms.com   |   Generated on ' . date('d M Y h:i A'), 0, 1, 'C');
			hms_pdf_apply_font($this, 'B', 8);
			$this->Cell(0, 4, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
		}
	}
}

if (!function_exists('hms_pdf_logo_path')) {
	function hms_pdf_logo_path() {
		$path = dirname(__DIR__) . '/assets/images/zantus-logo.jpg';
		return file_exists($path) ? $path : '';
	}
}

if (!function_exists('hms_pdf_font_file')) {
	function hms_pdf_font_file($family = 'helvetica', $style = '') {
		$family = strtolower(trim((string)$family));
		$style = strtoupper(trim((string)$style));
		$style = str_replace(['U', 'D', 'O'], '', $style);

		if ($family !== 'helvetica') {
			return '';
		}

		$fontMap = [
			'' => 'helvetica.php',
			'B' => 'helveticab.php',
			'I' => 'helveticai.php',
			'BI' => 'helveticabi.php',
			'IB' => 'helveticabi.php',
		];

		$fontFile = $fontMap[$style] ?? $fontMap[''];
		$fontPath = dirname(__DIR__) . '/vendor/tecnickcom/tcpdf/fonts/' . $fontFile;
		if (file_exists($fontPath)) {
			return $fontPath;
		}

		$fallbackPath = dirname(__DIR__) . '/vendor/tecnickcom/tcpdf/fonts/helvetica.php';
		return file_exists($fallbackPath) ? $fallbackPath : '';
	}
}

if (!function_exists('hms_pdf_apply_font')) {
	function hms_pdf_apply_font($pdf, $style = '', $size = 10, $family = 'helvetica') {
		$fontFile = hms_pdf_font_file($family, $style);
		if ($fontFile !== '') {
			$pdf->setFont($family, $style, $size, $fontFile);
			return;
		}
		$pdf->setFont($family, $style, $size);
	}
}

if (!function_exists('hms_create_pdf_document')) {
	function hms_create_pdf_document($title, $variant = 'receipt') {
		if (!hms_pdf_is_available()) {
			throw new \RuntimeException('TCPDF is not installed correctly on the server.');
		}

		$pdf = new HMSReceiptPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->hmsDocumentTitle = (string)$title;
		$pdf->hmsLogoPath = hms_pdf_logo_path();
		$pdf->hmsVariant = $variant === 'prescription' ? 'prescription' : 'receipt';
		$pdf->SetCreator('Zantus HMS');
		$pdf->SetAuthor('Zantus HMS');
		$pdf->SetTitle((string)$title);
		$pdf->SetSubject((string)$title);
		$topMargin = $pdf->hmsVariant === 'prescription' ? 50 : 58;
		$bottomMargin = $pdf->hmsVariant === 'prescription' ? 20 : 22;
		$pdf->SetMargins(14, $topMargin, 14);
		$pdf->SetHeaderMargin(0);
		$pdf->SetFooterMargin(0);
		$pdf->SetAutoPageBreak(true, $bottomMargin);
		hms_pdf_apply_font($pdf, '', 10);
		$pdf->AddPage();
		return $pdf;
	}
}

if (!function_exists('hms_pdf_text')) {
	function hms_pdf_text($value, $default = '-') {
		$value = trim((string)$value);
		return $value === '' ? $default : $value;
	}
}

if (!function_exists('hms_pdf_money')) {
	function hms_pdf_money($amount) {
		$numeric = is_numeric($amount) ? (float)$amount : 0.0;
		return 'Rs. ' . number_format($numeric, 2);
	}
}

if (!function_exists('hms_pdf_label_value_table')) {
	function hms_pdf_label_value_table(array $pairs) {
		$html = '<table cellpadding="5" cellspacing="0" border="1" style="border-color:#dbe4f0;">';
		foreach ($pairs as $label => $value) {
			$html .= '<tr>';
			$html .= '<td width="35%" style="background-color:#eff6ff;color:#1e3a8a;"><strong>' . htmlspecialchars((string)$label) . '</strong></td>';
			$html .= '<td width="65%">' . htmlspecialchars(hms_pdf_text($value)) . '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';
		return $html;
	}
}

if (!function_exists('hms_pdf_block_title')) {
	function hms_pdf_block_title($title) {
		return '<div style="font-size:12px;font-weight:bold;color:#1e3a8a;margin:8px 0 6px 0;">' . htmlspecialchars((string)$title) . '</div>';
	}
}

if (!function_exists('hms_pdf_html_escape')) {
	function hms_pdf_html_escape($value, $default = '-') {
		return htmlspecialchars(hms_pdf_text($value, $default), ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('hms_pdf_status_badge_html')) {
	function hms_pdf_status_badge_html($status) {
		$label = trim((string)$status);
		$key = strtolower($label);
		$background = '#dbeafe';
		$color = '#1d4ed8';

		if (strpos($key, 'paid') !== false || strpos($key, 'complete') !== false) {
			$background = '#d1fae5';
			$color = '#047857';
		} elseif (strpos($key, 'check') !== false || strpos($key, 'schedule') !== false) {
			$background = '#e0f2fe';
			$color = '#0369a1';
		} elseif (strpos($key, 'pending') !== false) {
			$background = '#fef3c7';
			$color = '#b45309';
		} elseif (strpos($key, 'cancel') !== false || strpos($key, 'failed') !== false) {
			$background = '#fee2e2';
			$color = '#b91c1c';
		}

		return '<span style="background-color:' . $background . ';color:' . $color . ';font-size:8px;font-weight:bold;padding:4px 9px;border-radius:12px;">' . hms_pdf_html_escape($label) . '</span>';
	}
}

if (!function_exists('hms_pdf_kv_grid_html')) {
	function hms_pdf_kv_grid_html(array $pairs, $columns = 2) {
		$columns = max(1, min(3, (int)$columns));
		$items = [];
		foreach ($pairs as $label => $value) {
			$items[] = ['label' => (string)$label, 'value' => (string)$value];
		}

		$cellWidth = $columns === 1 ? 100 : ($columns === 2 ? 48 : 31);
		$gapWidth = $columns === 1 ? 0 : ($columns === 2 ? 4 : 3.5);
		$html = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';

		for ($index = 0; $index < count($items); $index += $columns) {
			$html .= '<tr>';
			for ($column = 0; $column < $columns; ++$column) {
				$itemIndex = $index + $column;
				if ($column > 0) {
					$html .= '<td width="' . $gapWidth . '%"></td>';
				}

				if (isset($items[$itemIndex])) {
					$item = $items[$itemIndex];
					$html .= '<td width="' . $cellWidth . '%" style="border:1px solid #dbeafe;background-color:#ffffff;">';
					$html .= '<div style="font-size:7px;color:#64748b;letter-spacing:0.4px;text-transform:uppercase;padding:6px 7px 0 7px;">' . hms_pdf_html_escape($item['label']) . '</div>';
					$html .= '<div style="font-size:11px;color:#0f172a;font-weight:bold;padding:2px 7px 7px 7px;">' . hms_pdf_html_escape($item['value']) . '</div>';
					$html .= '</td>';
				} else {
					$html .= '<td width="' . $cellWidth . '%"></td>';
				}
			}
			$html .= '</tr>';
			$html .= '<tr><td colspan="' . (($columns * 2) - 1) . '" height="4"></td></tr>';
		}

		$html .= '</table>';
		return $html;
	}
}

if (!function_exists('hms_pdf_note_box_html')) {
	function hms_pdf_note_box_html($title, $body, $tone = 'blue') {
		$background = $tone === 'teal' ? '#ecfdf5' : '#f8fbff';
		$border = $tone === 'teal' ? '#a7f3d0' : '#dbeafe';
		$titleColor = $tone === 'teal' ? '#047857' : '#1d4ed8';

		return '<div style="border:1px solid ' . $border . ';background-color:' . $background . ';padding:10px 12px;">'
			. '<div style="font-size:10px;font-weight:bold;color:' . $titleColor . ';margin-bottom:4px;">' . hms_pdf_html_escape($title) . '</div>'
			. '<div style="font-size:9px;line-height:1.7;color:#334155;">' . nl2br(htmlspecialchars(trim((string)$body), ENT_QUOTES, 'UTF-8')) . '</div>'
			. '</div>';
	}
}

if (!function_exists('hms_pdf_simple_table_html')) {
	function hms_pdf_simple_table_html(array $headers, array $rows, array $widths = [], $headerBackground = '#2563eb', $headerColor = '#ffffff') {
		$columnCount = count($headers);
		if ($columnCount === 0) {
			return '';
		}

		if (empty($widths)) {
			$widths = array_fill(0, $columnCount, round(100 / $columnCount, 2));
		}

		$html = '<table cellpadding="6" cellspacing="0" border="0" width="100%">';
		$html .= '<tr>';
		foreach ($headers as $index => $header) {
			$width = isset($widths[$index]) ? (float)$widths[$index] : round(100 / $columnCount, 2);
			$html .= '<td width="' . $width . '%" style="background-color:' . $headerBackground . ';color:' . $headerColor . ';font-size:9px;font-weight:bold;">' . hms_pdf_html_escape($header) . '</td>';
		}
		$html .= '</tr>';

		if (empty($rows)) {
			$html .= '<tr><td width="100%" colspan="' . $columnCount . '" style="border:1px solid #e2e8f0;color:#64748b;text-align:center;">No records available.</td></tr>';
		} else {
			foreach ($rows as $rowIndex => $row) {
				$background = $rowIndex % 2 === 0 ? '#ffffff' : '#f8fafc';
				$html .= '<tr>';
				foreach ($headers as $index => $header) {
					$width = isset($widths[$index]) ? (float)$widths[$index] : round(100 / $columnCount, 2);
					$cell = isset($row[$index]) ? $row[$index] : '';
					$html .= '<td width="' . $width . '%" style="border:1px solid #e2e8f0;background-color:' . $background . ';font-size:9px;color:#0f172a;">' . $cell . '</td>';
				}
				$html .= '</tr>';
			}
		}

		$html .= '</table>';
		return $html;
	}
}

if (!function_exists('hms_pdf_add_logo_watermark')) {
	function hms_pdf_add_logo_watermark($pdf, $x = 58, $y = 95, $w = 95, $opacity = 0.06) {
		$logo = hms_pdf_logo_path();
		if ($logo === '' || !method_exists($pdf, 'setAlpha')) {
			return;
		}

		$pdf->setAlpha($opacity);
		$pdf->Image($logo, $x, $y, $w, 0, 'JPG', '', '', true, 300, '', false, false, 0, false, false, false);
		$pdf->setAlpha(1);
	}
}

if (!function_exists('hms_appointment_source_tables')) {
	function hms_appointment_source_tables($con) {
		$tables = [];
		foreach (['current_appointments', 'past_appointments', 'appointment'] as $tableName) {
			if (hms_table_exists($con, $tableName)) {
				$tables[] = $tableName;
			}
		}
		return array_values(array_unique($tables));
	}
}

if (!function_exists('hms_normalize_payment_status')) {
	function hms_normalize_payment_status(array $row) {
		$paymentStatus = trim((string)($row['paymentStatus'] ?? 'Pending'));
		$isPaid = in_array(strtolower($paymentStatus), ['paid', 'paid at hospital'], true)
			|| !empty($row['paymentRef'])
			|| !empty($row['paidAt']);

		if ($isPaid && (strcasecmp($paymentStatus, 'Paid at Hospital') === 0 || strcasecmp((string)($row['paymentOption'] ?? ''), 'PayLater') === 0)) {
			return 'Paid at Hospital';
		}
		if ($isPaid) {
			return 'Paid';
		}
		if ($paymentStatus !== '') {
			return $paymentStatus;
		}
		return 'Pending';
	}
}

if (!function_exists('hms_resolve_visit_status')) {
	function hms_resolve_visit_status(array $row) {
		$visitStatus = trim((string)($row['visitStatus'] ?? 'Scheduled'));
		return $visitStatus === '' ? 'Scheduled' : $visitStatus;
	}
}

if (!function_exists('hms_find_user_appointment')) {
	function hms_find_user_appointment($con, $appointmentId, $userId) {
		$appointmentId = (int)$appointmentId;
		$userId = (int)$userId;

		if ($appointmentId <= 0 || $userId <= 0) {
			return null;
		}

		foreach (hms_appointment_source_tables($con) as $tableName) {
			$sql = "SELECT a.*, u.fullName AS patientName, u.email AS patientEmail, d.doctorName, d.docEmail
				FROM $tableName a
				JOIN users u ON u.id = a.userId
				JOIN doctors d ON d.id = a.doctorId
				WHERE a.id = $1 AND a.userId = $2
				LIMIT 1";
			$result = hms_query_params($con, $sql, [$appointmentId, $userId]);
			$row = $result ? hms_fetch_assoc($result) : null;
			if ($row) {
				$row['sourceTable'] = $tableName;
				$row['paymentStatusResolved'] = hms_normalize_payment_status($row);
				$row['visitStatusResolved'] = hms_resolve_visit_status($row);
				return $row;
			}
		}

		return null;
	}
}

if (!function_exists('hms_find_user_payment_record')) {
	function hms_find_user_payment_record($con, $appointmentId, $userId) {
		$appointment = hms_find_user_appointment($con, $appointmentId, $userId);
		if (!$appointment) {
			return [null, null];
		}

		$payment = null;
		if (hms_table_exists($con, 'payment_transactions')) {
			$paymentResult = hms_query_params(
				$con,
				"SELECT * FROM payment_transactions WHERE appointment_id=$1 AND user_id=$2 ORDER BY paid_at DESC, id DESC LIMIT 1",
				[(int)$appointmentId, (int)$userId]
			);
			$payment = $paymentResult ? hms_fetch_assoc($paymentResult) : null;
		}

		if (!$payment && in_array(strtolower((string)$appointment['paymentStatusResolved']), ['paid', 'paid at hospital'], true)) {
			$payment = [
				'appointment_id' => (int)$appointment['id'],
				'user_id' => (int)$appointment['userId'],
				'amount' => (float)($appointment['consultancyFees'] ?? 0),
				'payment_method' => strcasecmp((string)$appointment['paymentStatusResolved'], 'Paid at Hospital') === 0 ? 'Pay at Hospital' : 'Online',
				'transaction_ref' => (string)($appointment['paymentRef'] ?? ''),
				'status' => (string)$appointment['paymentStatusResolved'],
				'paid_at' => (string)($appointment['paidAt'] ?? ''),
				'created_at' => (string)($appointment['paidAt'] ?? ''),
			];
		}

		return [$appointment, $payment];
	}
}

if (!function_exists('hms_parse_medicines_for_pdf')) {
	function hms_parse_medicines_for_pdf($medicinesText) {
		$rows = [];
		$medicinesText = trim((string)$medicinesText);
		if ($medicinesText === '') {
			return $rows;
		}

		$lines = preg_split('/\r\n|\r|\n/', $medicinesText);
		foreach ($lines as $line) {
			$line = trim((string)$line);
			if ($line === '') {
				continue;
			}

			$item = [
				'medicine_name' => '-',
				'dosage' => '-',
				'frequency' => '-',
				'duration' => '-',
				'instructions' => '-',
			];

			foreach (array_map('trim', explode('|', $line)) as $part) {
				if (stripos($part, 'Medicine:') === 0) {
					$item['medicine_name'] = trim(substr($part, 9));
				} elseif (stripos($part, 'Dosage:') === 0) {
					$item['dosage'] = trim(substr($part, 7));
				} elseif (stripos($part, 'Frequency:') === 0) {
					$item['frequency'] = trim(substr($part, 10));
				} elseif (stripos($part, 'Duration:') === 0) {
					$item['duration'] = trim(substr($part, 9));
				} elseif (stripos($part, 'Instructions:') === 0) {
					$item['instructions'] = trim(substr($part, 13));
				}
			}

			$rows[] = $item;
		}

		return $rows;
	}
}

if (!function_exists('hms_find_user_prescription')) {
	function hms_find_user_prescription($con, $userId, $prescriptionId = 0, $appointmentId = 0) {
		$userId = (int)$userId;
		$prescriptionId = (int)$prescriptionId;
		$appointmentId = (int)$appointmentId;

		if (!hms_table_exists($con, 'prescriptions') || $userId <= 0) {
			return null;
		}

		$prescription = null;
		if ($prescriptionId > 0) {
			$result = hms_query_params(
				$con,
				"SELECT p.*, u.fullName AS patientName, d.doctorName
				FROM prescriptions p
				JOIN users u ON u.id=p.patient_id
				JOIN doctors d ON d.id=p.doctor_id
				WHERE p.id=$1 AND p.patient_id=$2
				LIMIT 1",
				[$prescriptionId, $userId]
			);
			$prescription = $result ? hms_fetch_assoc($result) : null;
		}

		if (!$prescription && $appointmentId > 0) {
			$result = hms_query_params(
				$con,
				"SELECT p.*, u.fullName AS patientName, d.doctorName
				FROM prescriptions p
				JOIN users u ON u.id=p.patient_id
				JOIN doctors d ON d.id=p.doctor_id
				WHERE p.appointment_id=$1 AND p.patient_id=$2
				ORDER BY p.id DESC LIMIT 1",
				[$appointmentId, $userId]
			);
			$prescription = $result ? hms_fetch_assoc($result) : null;
		}

		if (!$prescription && $appointmentId > 0) {
			$appointment = hms_find_user_appointment($con, $appointmentId, $userId);
			$doctorId = (int)($appointment['doctorId'] ?? 0);
			if ($doctorId > 0) {
				$fallback = hms_query_params(
					$con,
					"SELECT p.*, u.fullName AS patientName, d.doctorName
					FROM prescriptions p
					JOIN users u ON u.id=p.patient_id
					JOIN doctors d ON d.id=p.doctor_id
					WHERE p.patient_id=$1 AND p.doctor_id=$2
					ORDER BY p.id DESC LIMIT 1",
					[$userId, $doctorId]
				);
				$prescription = $fallback ? hms_fetch_assoc($fallback) : null;
			}
		}

		if ($prescription) {
			$prescription['medicineRows'] = hms_parse_medicines_for_pdf($prescription['medicines'] ?? '');
		}

		return $prescription;
	}
}

if (!function_exists('hms_pdf_output_inline')) {
	function hms_pdf_output_inline($pdf, $filename) {
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		$pdf->Output($filename, 'I');
		exit();
	}
}
?>

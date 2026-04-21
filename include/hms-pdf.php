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

		public function Header() {
			$this->SetFillColor(30, 58, 138);
			$this->Rect(0, 0, $this->getPageWidth(), 30, 'F');

			if ($this->hmsLogoPath !== '' && file_exists($this->hmsLogoPath)) {
				$this->Image($this->hmsLogoPath, 12, 6, 16, 16, 'JPG', '', '', true, 300, '', false, false, 0, false, false, false);
			}

			$this->SetTextColor(255, 255, 255);
			hms_pdf_apply_font($this, 'B', 16);
			$this->SetXY(32, 7);
			$this->Cell(0, 7, 'Zantus HMS', 0, 1, 'L', false, '', 0, false, 'T', 'M');

			hms_pdf_apply_font($this, '', 9);
			$this->SetX(32);
			$this->Cell(0, 5, $this->hmsSubtitle, 0, 1, 'L', false, '', 0, false, 'T', 'M');

			$this->SetTextColor(30, 58, 138);
			hms_pdf_apply_font($this, 'B', 14);
			$this->SetXY(12, 33);
			$this->Cell(0, 8, $this->hmsDocumentTitle, 0, 1, 'L', false, '', 0, false, 'T', 'M');
		}

		public function Footer() {
			$this->SetY(-15);
			$this->SetDrawColor(203, 213, 225);
			$this->Line(12, $this->GetY(), $this->getPageWidth() - 12, $this->GetY());
			$this->SetY(-12);
			$this->SetTextColor(100, 116, 139);
			hms_pdf_apply_font($this, '', 8);
			$this->Cell(0, 6, 'Generated on ' . date('d M Y h:i A') . ' | Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
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
	function hms_create_pdf_document($title) {
		if (!hms_pdf_is_available()) {
			throw new \RuntimeException('TCPDF is not installed correctly on the server.');
		}

		$pdf = new HMSReceiptPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->hmsDocumentTitle = (string)$title;
		$pdf->hmsLogoPath = hms_pdf_logo_path();
		$pdf->SetCreator('Zantus HMS');
		$pdf->SetAuthor('Zantus HMS');
		$pdf->SetTitle((string)$title);
		$pdf->SetSubject((string)$title);
		$pdf->SetMargins(12, 45, 12);
		$pdf->SetHeaderMargin(0);
		$pdf->SetFooterMargin(10);
		$pdf->SetAutoPageBreak(true, 22);
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

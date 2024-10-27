<?php 
require_once dirname(dirname(__FILE__)). '/vendor/autoload.php';

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;

Class AGP_Qrcode {
	
	public function generateQR($qrcode) {
		echo '<img src="'.(new QRCode)->render($qrcode).'" alt="QR Code" />';
	}
	
}

global $AGP_Qrcode;
$AGP_Qrcode = new AGP_Qrcode();
<?php

namespace App\Service;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeService
{
    public function generate($data)
    {
    	$writer = new PngWriter();

		$qrCode = QrCode::create($data)
		    ->setEncoding(new Encoding('UTF-8'))
		    ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
		    ->setSize(175)
		    ->setMargin(0)
		    ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
		    ->setForegroundColor(new Color(0, 0, 0))
		    ->setBackgroundColor(new Color(255, 255, 255));

		$result = $writer->write($qrCode);

		return base64_encode($result->getString());
    }
}

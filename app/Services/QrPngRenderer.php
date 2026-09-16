<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

class QrPngRenderer
{
    public static function generate(string $content, int $size = 300, int $margin = 1): string
    {
        if (!extension_loaded('gd')) {
            abort(500, 'The GD extension is required to generate PNG QR codes but is not enabled.');
        }

        $qrCode = Encoder::encode($content, ErrorCorrectionLevel::M());
        $matrix = $qrCode->getMatrix();
        $matrixSize = $matrix->getWidth();

        $quietZone = $margin * 4;
        $totalModules = $matrixSize + $quietZone * 2;
        $moduleSize = (int) floor($size / $totalModules);

        if ($moduleSize < 1) {
            $moduleSize = 1;
        }

        $imageSize = $totalModules * $moduleSize;
        $image = imagecreatetruecolor($imageSize, $imageSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $white);

        $bytes = $matrix->getArray();
        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($bytes[$y][$x] & 1) {
                    $pixelX = ($quietZone + $x) * $moduleSize;
                    $pixelY = ($quietZone + $y) * $moduleSize;
                    imagefilledrectangle($image, $pixelX, $pixelY, $pixelX + $moduleSize - 1, $pixelY + $moduleSize - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($image);
        $pngData = ob_get_clean();
        imagedestroy($image);

        return $pngData;
    }
}

<?php

namespace App\Services;

use App\Models\Membership;
use Barryvdh\DomPDF\Facade\Pdf;

class MemberCardService
{
    private const WIDTH = 1120;
    private const HEIGHT = 672;

    public function generatePng(Membership $membership): string
    {
        $im = $this->createCardCanvas($membership);
        ob_start();
        imagepng($im);
        $content = ob_get_clean();
        imagedestroy($im);
        return $content;
    }

    public function generateJpeg(Membership $membership, int $quality = 95): string
    {
        $im = $this->createCardCanvas($membership);
        $bgCanvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $black = imagecolorallocate($bgCanvas, 10, 14, 23);
        imagefill($bgCanvas, 0, 0, $black);
        imagecopy($bgCanvas, $im, 0, 0, 0, 0, self::WIDTH, self::HEIGHT);

        ob_start();
        imagejpeg($bgCanvas, null, $quality);
        $content = ob_get_clean();
        imagedestroy($bgCanvas);
        imagedestroy($im);
        return $content;
    }

    public function generatePdf(Membership $membership): string
    {
        $pngBase64 = base64_encode($this->generatePng($membership));

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page {
    margin: 0;
    size: 1120px 672px;
}
body {
    margin: 0;
    padding: 0;
    background-color: #07090e;
}
.card-img {
    width: 1120px;
    height: 672px;
    display: block;
}
</style>
</head>
<body>
    <img class="card-img" src="data:image/png;base64,' . $pngBase64 . '">
</body>
</html>';

        return Pdf::loadHTML($html)
            ->setPaper([0, 0, 1120, 672])
            ->output();
    }

    private function createCardCanvas(Membership $membership): \GdImage
    {
        $w = self::WIDTH;
        $h = self::HEIGHT;

        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        $colorTopLeft = [10, 14, 23];
        $colorBottomRight = [18, 11, 26];

        for ($y = 0; $y < $h; $y++) {
            $ratioY = $y / $h;
            for ($x = 0; $x < $w; $x += 4) {
                $ratioX = $x / $w;
                $diagRatio = ($ratioX * 0.45 + $ratioY * 0.55);
                $r = (int) ($colorTopLeft[0] * (1 - $diagRatio) + $colorBottomRight[0] * $diagRatio);
                $g = (int) ($colorTopLeft[1] * (1 - $diagRatio) + $colorBottomRight[1] * $diagRatio);
                $b = (int) ($colorTopLeft[2] * (1 - $diagRatio) + $colorBottomRight[2] * $diagRatio);
                $pixelColor = imagecolorallocate($im, $r, $g, $b);
                imagefilledrectangle($im, $x, $y, min($x + 3, $w - 1), $y, $pixelColor);
            }
        }

        $fontBold = file_exists('C:\\Windows\\Fonts\\segoeuib.ttf') 
            ? 'C:\\Windows\\Fonts\\segoeuib.ttf' 
            : (file_exists('C:\\Windows\\Fonts\\arialbd.ttf') ? 'C:\\Windows\\Fonts\\arialbd.ttf' : realpath(public_path('fonts/SpaceGrotesk.ttf')));

        $fontRegular = file_exists('C:\\Windows\\Fonts\\segoeui.ttf') 
            ? 'C:\\Windows\\Fonts\\segoeui.ttf' 
            : (file_exists('C:\\Windows\\Fonts\\arial.ttf') ? 'C:\\Windows\\Fonts\\arial.ttf' : realpath(public_path('fonts/Sora.ttf')));

        $fontSpaceGrotesk = realpath(public_path('fonts/SpaceGrotesk.ttf')) ?: $fontBold;
        $fontSora = realpath(public_path('fonts/Sora.ttf')) ?: $fontRegular;

        $colWhite = imagecolorallocate($im, 255, 255, 255);
        $colGold = imagecolorallocate($im, 255, 210, 31);
        $colMutedBlue = imagecolorallocate($im, 148, 163, 184);
        $colCoral = imagecolorallocate($im, 220, 100, 105);
        $colBorder = imagecolorallocate($im, 122, 22, 24);
        $colDivider = imagecolorallocate($im, 26, 36, 52);

        $this->drawRoundedBorder($im, 34, 34, $w - 68, $h - 68, 54, 5, $colBorder);

        $logoPath = public_path('images/COMSOC.png');
        if (file_exists($logoPath)) {
            $logoIm = imagecreatefrompng($logoPath);
            if ($logoIm) {
                $origW = imagesx($logoIm);
                $origH = imagesy($logoIm);
                $destSize = 88;
                $destX = 96;
                $destY = 72;
                imagecopyresampled($im, $logoIm, $destX, $destY, 0, 0, $destSize, $destSize, $origW, $origH);
                imagedestroy($logoIm);
            }
        }

        $this->drawBoldText($im, 34, 0, 215, 114, $colWhite, $fontBold, 'Computing Society', 2);
        $this->drawBoldText($im, 21, 0, 215, 154, $colGold, $fontBold, 'OFFICIAL MEMBER', 2);

        $ayRaw = $membership->academicYear?->label ?? '2025–2026';
        $ayLabel = trim(preg_replace('/^A\.?Y\.?\s*/i', '', $ayRaw));
        $ayLabel = str_replace('-', '–', $ayLabel);
        $ayText = "AY  {$ayLabel}";
        $ayBox = imagettfbbox(25, 0, $fontRegular, $ayText);
        $ayWidth = abs($ayBox[4] - $ayBox[0]);
        $ayX = ($w - 96) - $ayWidth;
        imagettftext($im, 25, 0, $ayX, 128, $colMutedBlue, $fontRegular, $ayText);

        $dividerY1 = 196;
        for ($dy = 0; $dy < 2; $dy++) {
            imageline($im, 96, $dividerY1 + $dy, $w - 96, $dividerY1 + $dy, $colDivider);
        }

        $student = $membership->student;
        $nameText = mb_strtoupper($student ? ($student->first_name . ' ' . $student->last_name) : 'JUAN DELA CRUZ');

        $nameFontSize = 35;
        $maxNameWidth = 630;
        do {
            $box = imagettfbbox($nameFontSize, 0, $fontBold, $nameText);
            $nameW = abs($box[4] - $box[0]);
            if ($nameW <= $maxNameWidth || $nameFontSize <= 18) {
                break;
            }
            $nameFontSize -= 2;
        } while ($nameFontSize > 18);

        $this->drawBoldText($im, $nameFontSize, 0, 96, 308, $colWhite, $fontBold, $nameText, 2);

        $studentNumber = $student->student_number ?? '2023-10023-BN-0';
        $this->drawBoldText($im, 26, 0, 96, 368, $colCoral, $fontBold, $studentNumber, 1);

        $program = $student->program ?: 'BSIT';
        $yearLevel = $student->year_level ?: '3rd Year';
        $progYearText = "{$program} \u{2022} {$yearLevel}";
        imagettftext($im, 26, 0, 96, 428, $colMutedBlue, $fontRegular, $progYearText);

        $qrCode = $membership->activeQrCode ?? $membership->latestQrCode;
        $qrToken = $qrCode ? $qrCode->token : ($membership->membership_number ?? 'SAMPLE_ATTENDANCE_PASS');

        $qrBoxSize = 205;
        $qrX = $w - 96 - $qrBoxSize;
        $qrY = 250;
        $qrRadius = 34;

        $colQrBg = imagecolorallocate($im, 255, 255, 255);
        $this->drawFilledRoundedRect($im, $qrX, $qrY, $qrBoxSize, $qrBoxSize, $qrRadius, $colQrBg);

        $rawQrPng = QrPngRenderer::generate($qrToken, 165, 0);
        $qrSubIm = @imagecreatefromstring($rawQrPng);
        if ($qrSubIm) {
            $subW = imagesx($qrSubIm);
            $subH = imagesy($qrSubIm);
            $innerQrSize = 165;
            $innerX = $qrX + (int)(($qrBoxSize - $innerQrSize) / 2);
            $innerY = $qrY + (int)(($qrBoxSize - $innerQrSize) / 2);
            imagecopyresampled($im, $qrSubIm, $innerX, $innerY, 0, 0, $innerQrSize, $innerQrSize, $subW, $subH);
            imagedestroy($qrSubIm);
        }

        $dividerY2 = 494;
        for ($dy = 0; $dy < 2; $dy++) {
            imageline($im, 96, $dividerY2 + $dy, $w - 96, $dividerY2 + $dy, $colDivider);
        }

        $memNum = $membership->membership_number ?? ('MEM-' . date('Y') . '-' . str_pad($membership->id ?? '1', 4, '0', STR_PAD_LEFT));
        imagettftext($im, 21, 0, 96, 552, $colMutedBlue, $fontRegular, $memNum);

        $authText = 'AUTHORIZED ATTENDANCE PASS';
        $authBox = imagettfbbox(21, 0, $fontRegular, $authText);
        $authW = abs($authBox[4] - $authBox[0]);
        $authX = ($w - 96) - $authW;
        imagettftext($im, 21, 0, $authX, 552, $colMutedBlue, $fontRegular, $authText);

        $this->applyOuterCornerMask($im, 34, 34, $w - 68, $h - 68, 54);

        return $im;
    }

    private function drawBoldText(\GdImage $im, int $size, int $angle, int $x, int $y, int $color, string $font, string $text, int $weight = 1): void
    {
        for ($ox = 0; $ox <= $weight; $ox++) {
            for ($oy = 0; $oy <= $weight; $oy++) {
                imagettftext($im, $size, $angle, $x + $ox, $y + $oy, $color, $font, $text);
            }
        }
    }

    private function drawRoundedBorder(\GdImage $im, int $x, int $y, int $w, int $h, int $r, int $thickness, int $color): void
    {
        for ($t = 0; $t < $thickness; $t++) {
            $curX = $x + $t;
            $curY = $y + $t;
            $curW = $w - ($t * 2);
            $curH = $h - ($t * 2);
            $curR = max(1, $r - $t);

            imageline($im, $curX + $curR, $curY, $curX + $curW - $curR, $curY, $color);
            imageline($im, $curX + $curR, $curY + $curH - 1, $curX + $curW - $curR, $curY + $curH - 1, $color);
            imageline($im, $curX, $curY + $curR, $curX, $curY + $curH - $curR, $color);
            imageline($im, $curX + $curW - 1, $curY + $curR, $curX + $curW - 1, $curY + $curH - 1 - $curR, $color);

            imagearc($im, $curX + $curR, $curY + $curR, $curR * 2, $curR * 2, 180, 270, $color);
            imagearc($im, $curX + $curW - $curR - 1, $curY + $curR, $curR * 2, $curR * 2, 270, 360, $color);
            imagearc($im, $curX + $curW - $curR - 1, $curY + $curH - $curR - 1, $curR * 2, $curR * 2, 0, 90, $color);
            imagearc($im, $curX + $curR, $curY + $curH - $curR - 1, $curR * 2, $curR * 2, 90, 180, $color);
        }
    }

    private function drawFilledRoundedRect(\GdImage $im, int $x, int $y, int $w, int $h, int $r, int $color): void
    {
        imagefilledrectangle($im, $x + $r, $y, $x + $w - $r, $y + $h, $color);
        imagefilledrectangle($im, $x, $y + $r, $x + $w, $y + $h - $r, $color);

        imagefilledellipse($im, $x + $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
    }

    private function applyOuterCornerMask(\GdImage $im, int $x, int $y, int $w, int $h, int $r): void
    {
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagealphablending($im, false);

        for ($i = 0; $i < $y; $i++) {
            imageline($im, 0, $i, self::WIDTH - 1, $i, $transparent);
            imageline($im, 0, self::HEIGHT - 1 - $i, self::WIDTH - 1, self::HEIGHT - 1 - $i, $transparent);
        }
        for ($i = 0; $i < $x; $i++) {
            imageline($im, $i, 0, $i, self::HEIGHT - 1, $transparent);
            imageline($im, self::WIDTH - 1 - $i, 0, self::WIDTH - 1 - $i, self::HEIGHT - 1, $transparent);
        }

        for ($cy = 0; $cy < $r; $cy++) {
            for ($cx = 0; $cx < $r; $cx++) {
                $dx = $r - $cx;
                $dy = $r - $cy;
                if (($dx * $dx + $dy * $dy) > ($r * $r)) {
                    imagesetpixel($im, $x + $cx, $y + $cy, $transparent);
                    imagesetpixel($im, $x + $w - 1 - $cx, $y + $cy, $transparent);
                    imagesetpixel($im, $x + $w - 1 - $cx, $y + $h - 1 - $cy, $transparent);
                    imagesetpixel($im, $x + $cx, $y + $h - 1 - $cy, $transparent);
                }
            }
        }

        imagealphablending($im, true);
    }
}

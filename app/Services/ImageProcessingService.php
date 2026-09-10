<?php

namespace App\Services;

use Imagick;
use ImagickPixel;

class ImageProcessingService
{
    /**
     * Convert white / near-white pixels to transparent.
     * Prefer Imagick if available; fallback to GD.
     */
    public function makeWhiteTransparent(string $binary): string
    {
        // ====== 1) Imagick (Preferred) ======
        if (extension_loaded('imagick')) {
            try {
                $img = new Imagick;
                $img->readImageBlob($binary);
                $img->setImageColorspace(Imagick::COLORSPACE_SRGB);
                $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
                $img->setImageFormat('png');

                $w = $img->getImageWidth();
                $h = $img->getImageHeight();

                // Determine background color from 4 corners (average)
                $points = [
                    [5, 5],
                    [max(0, $w - 6), 5],
                    [5, max(0, $h - 6)],
                    [max(0, $w - 6), max(0, $h - 6)],
                ];

                $sum = ['r' => 0, 'g' => 0, 'b' => 0];
                foreach ($points as [$x, $y]) {
                    $px = $img->getImagePixelColor($x, $y)->getColor(true); // 0..1
                    $sum['r'] += $px['r'];
                    $sum['g'] += $px['g'];
                    $sum['b'] += $px['b'];
                }
                $avg = [
                    'r' => (int) round(($sum['r'] / 4) * 255),
                    'g' => (int) round(($sum['g'] / 4) * 255),
                    'b' => (int) round(($sum['b'] / 4) * 255),
                ];

                $bg = new ImagickPixel(sprintf('rgb(%d,%d,%d)', $avg['r'], $avg['g'], $avg['b']));

                // Fuzz needs to be QuantumRange value (not percentage)
                $range = Imagick::getQuantumRange();
                $quant = $range['quantumRangeLong'] ?? 65535;
                // For images with slight shadows/beige, 20–30% is good. Start with 0.25
                $fuzzRatio = 0.25;
                $fuzz = (int) round($quant * $fuzzRatio);

                // Wipe background (and near colors) to transparent
                $img->setImageColorFuzz($fuzz);
                $img->transparentPaintImage($bg, 0.0, $fuzz, false);

                // (Optional) Extra pass on pure white
                $img->transparentPaintImage(new ImagickPixel('white'), 0.0, (int) round($quant * 0.12), false);

                // (Optional) Trim beige edges after transparency
                // $img->trimImage(1);

                $out = $img->getImageBlob();
                $img->clear();
                $img->destroy();

                return $out;
            } catch (\Throwable $e) {
                // Fallback to GD
            }
        }

        // ====== 2) GD fallback ======
        $gd = @imagecreatefromstring($binary);
        if (! $gd) {
            return $binary;
        }

        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($gd);
        }
        imagesavealpha($gd, true);
        imagealphablending($gd, false);

        $w = imagesx($gd);
        $h = imagesy($gd);

        // Determine background color from corners
        $sample = function ($x, $y) use ($gd) {
            $c = imagecolorat($gd, $x, $y);

            return [($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF];
        };
        $p1 = $sample(5, 5);
        $p2 = $sample(max(0, $w - 6), 5);
        $p3 = $sample(5, max(0, $h - 6));
        $p4 = $sample(max(0, $w - 6), max(0, $h - 6));

        $bgR = (int) round(($p1[0] + $p2[0] + $p3[0] + $p4[0]) / 4);
        $bgG = (int) round(($p1[1] + $p2[1] + $p3[1] + $p4[1]) / 4);
        $bgB = (int) round(($p1[2] + $p2[2] + $p3[2] + $p4[2]) / 4);

        // Consider close to background if color distance is small
        $deltaThreshold = 45; // Increase to 55–70 if stronger shadows exist

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($gd, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $dr = $r - $bgR;
                $dg = $g - $bgG;
                $db = $b - $bgB;
                $delta = sqrt($dr * $dr + $dg * $dg + $db * $db);

                if ($delta <= $deltaThreshold) {
                    $transparent = imagecolorallocatealpha($gd, 255, 255, 255, 127);
                    imagesetpixel($gd, $x, $y, $transparent);
                }
            }
        }

        ob_start();
        imagepng($gd);
        $out = ob_get_clean();
        imagedestroy($gd);

        return $out ?: $binary;
    }
}

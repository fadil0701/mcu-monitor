<?php

namespace App\Support;

final class MathCaptchaImageRenderer
{
    private const WIDTH = 280;

    private const HEIGHT = 80;

    /** @return string PNG bytes */
    public static function render(string $expression): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $background = imagecolorallocate($image, 236, 236, 236);
        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $background);

        for ($i = 0; $i < 450; $i++) {
            $noise = imagecolorallocate(
                $image,
                random_int(205, 245),
                random_int(205, 245),
                random_int(205, 245),
            );
            imagesetpixel($image, random_int(0, self::WIDTH - 1), random_int(0, self::HEIGHT - 1), $noise);
        }

        for ($i = 0; $i < 7; $i++) {
            $line = imagecolorallocate(
                $image,
                random_int(90, 210),
                random_int(40, 150),
                random_int(40, 170),
            );
            imageline(
                $image,
                random_int(0, self::WIDTH),
                random_int(0, self::HEIGHT),
                random_int(0, self::WIDTH),
                random_int(0, self::HEIGHT),
                $line,
            );
        }

        $fontPath = self::fontPath();
        $x = 20;
        $chars = preg_split('//u', trim($expression), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($chars as $char) {
            $color = imagecolorallocate(
                $image,
                random_int(25, 190),
                random_int(25, 170),
                random_int(25, 170),
            );

            if ($fontPath !== null) {
                $size = random_int(36, 44);
                $angle = random_int(-10, 10);
                $baseline = (int) round(self::HEIGHT * 0.72) + random_int(-2, 2);
                imagettftext($image, $size, $angle, $x, $baseline, $color, $fontPath, $char);
                $x += match ($char) {
                    ' ' => 14,
                    '+' => 34,
                    default => 32,
                };
            } else {
                imagestring($image, 5, $x, random_int(28, 36), $char, $color);
                $x += 22;
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private static function fontPath(): ?string
    {
        foreach ([
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
        ] as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}

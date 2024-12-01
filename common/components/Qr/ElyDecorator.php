<?php
namespace common\components\Qr;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\QrCode;
use BaconQrCode\Renderer\Image\Decorator\DecoratorInterface;
use BaconQrCode\Renderer\Image\RendererInterface;
use BaconQrCode\Renderer\Image\Svg;
use Imagick;
use InvalidArgumentException;
use ReflectionClass;

class ElyDecorator implements DecoratorInterface {

    private const LOGO = __DIR__ . '/resources/logo.svg';

    private const CORRECTION_MAP = [
        ErrorCorrectionLevel::L => 7,
        ErrorCorrectionLevel::M => 15,
        ErrorCorrectionLevel::Q => 25,
        ErrorCorrectionLevel::H => 30,
    ];

    /**
     * @throws \ImagickException
     * @throws \ImagickPixelException
     * @throws \ImagickPixelIteratorException
     */
    public function preProcess(
        QrCode $qrCode,
        RendererInterface $renderer,
        $outputWidth,
        $outputHeight,
        $leftPadding,
        $topPadding,
        $multiple,
    ): void {
        if (!$renderer instanceof Svg) {
            throw new InvalidArgumentException('$renderer must by instance of ' . Svg::class);
        }

        $correctionLevel = self::CORRECTION_MAP[$qrCode->getErrorCorrectionLevel()->get()];
        $sizeMultiplier = $correctionLevel + floor($correctionLevel / 3);
        $count = $qrCode->getMatrix()->getWidth();

        $countToRemoveX = (int)floor($count * $sizeMultiplier / 100);
        $countToRemoveY = (int)floor($count * $sizeMultiplier / 100);

        $startX = (int)($leftPadding + round(($count - $countToRemoveX) / 2 * $multiple));
        $startY = (int)($topPadding + round(($count - $countToRemoveY) / 2 * $multiple));
        $width = $countToRemoveX * $multiple;
        $height = $countToRemoveY * $multiple;

        $reflection = new ReflectionClass($renderer);
        $property = $reflection->getProperty('svg');
        $property->setAccessible(true);
        /** @var \SimpleXMLElement $svg */
        $svg = $property->getValue($renderer);

        /** @var \SimpleXMLElement $image */
        $image = $svg->addChild('image');
        $image->addAttribute('x', (string)$startX);
        $image->addAttribute('y', (string)$startY);
        $image->addAttribute('width', (string)$width);
        $image->addAttribute('height', (string)$height);
        $image->addAttribute('xlink:href', $this->encodeSvgToBase64(self::LOGO));

        $logo = new Imagick();
        $logo->readImage(self::LOGO);
        $logo->scaleImage($width, $height);

        $foundedPixels = [];
        foreach ($logo->getPixelIterator() as $row => $pixels) {
            /** @var \ImagickPixel[] $pixels */
            foreach ($pixels as $column => $pixel) {
                $color = $pixel->getColorAsString();
                if ($color !== 'srgb(255,255,255)') {
                    $foundedPixels[] = [(int)($startX + $column), (int)($startY + $row)];
                }
            }
        }

        $logo->clear();
        $logo->destroy();

        $padding = $multiple - 2;
        if ($padding < 0) {
            $padding = 1;
        }

        foreach ($foundedPixels as $coordinates) {
            [$x, $y] = $coordinates;
            $x -= $leftPadding;
            $y -= $topPadding;

            for ($i = $x - $padding; $i <= $x + $padding; $i++) {
                for ($j = $y - $padding; $j <= $y + $padding; $j++) {
                    $matrixX = (int)floor($i / $multiple);
                    $matrixY = (int)floor($j / $multiple);
                    $qrCode->getMatrix()->set($matrixX, $matrixY, 0);
                }
            }
        }
    }

    public function postProcess(
        QrCode $qrCode,
        RendererInterface $renderer,
        $outputWidth,
        $outputHeight,
        $leftPadding,
        $topPadding,
        $multiple,
    ) {
    }

    private function encodeSvgToBase64(string $filePath): string {
        return 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($filePath));
    }

}

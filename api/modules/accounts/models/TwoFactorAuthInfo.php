<?php
namespace api\modules\accounts\models;

use api\exceptions\ThisShouldNotHappenException;
use api\models\base\BaseAccountForm;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\Svg;
use BaconQrCode\Writer;
use common\components\Qr\ElyDecorator;
use ParagonIE\ConstantTime\Base32;

class TwoFactorAuthInfo extends BaseAccountForm {
    use TotpHelper;

    public function getCredentials(): array {
        if (empty($this->getAccount()->otp_secret)) {
            $this->setOtpSecret();
        }

        $provisioningUri = $this->getTotp()->getProvisioningUri();

        return [
            'qr' => $this->buildDataImage($this->drawQrCode($provisioningUri)),
            'uri' => $provisioningUri,
            'secret' => $this->getAccount()->otp_secret,
        ];
    }

    private function drawQrCode(string $content): string {
        $content = $this->forceMinimalQrContentLength($content);

        $renderer = new Svg();
        $renderer->setForegroundColor(new Rgb(32, 126, 92));
        $renderer->setMargin(0);
        $renderer->addDecorator(new ElyDecorator());

        $writer = new Writer($renderer);

        return $writer->writeString($content, Encoder::DEFAULT_BYTE_MODE_ECODING, ErrorCorrectionLevel::H);
    }

    private function buildDataImage(string $svg) {
        $svg = trim($svg);
        // https://stackoverflow.com/a/30733736/5184751
        $svg = str_replace('#', '%23', $svg);

        return 'data:image/svg+xml,' . $svg;
    }

    /**
     * @param int $length
     * @throws ThisShouldNotHappenException
     */
    private function setOtpSecret(int $length = 24): void {
        $account = $this->getAccount();
        $account->otp_secret = $this->generateOtpSecret($length);
        if (!$account->save()) {
            throw new ThisShouldNotHappenException('Cannot set account otp_secret');
        }
    }

    /**
     * In the used library for rendering QR codes there is no possibility to specify a QR code version.
     * http://www.qrcode.com/en/about/version.html
     *
     * For some reason, generated versions 7 and 8 are not readable at all, with or without a logo.
     * Therefore, it is necessary to initially append the string to the length of version 9 (91),
     * adding to the end of the string the necessary number of characters "#".
     * This symbol is used because our content is a link and in order not to enter unnecessary parameters
     * we mark the additional part as a hash part and all application for scanning QR codes continue their work.
     *
     * @param string $content
     * @return string
     */
    private function forceMinimalQrContentLength(string $content): string {
        return str_pad($content, 91, '#');
    }

    /**
     * otp_secret is encoded in Base32, but after encoding there are characters in the result line
     * that can be mixed up (1 and l, O and 0, etc.). Since the target string isn't intended for
     * reverse decryption, we can safely delete them. The resulting string is 160% of the source line.
     * That's why, when generating the initial random bytes, we should provide such a length that
     * 160% of it is equal to the requested length.
     *
     * @param int $length
     * @return string
     */
    private function generateOtpSecret(int $length): string {
        $randomBytesLength = ceil($length / 1.6);
        $result = '';
        while (strlen($result) < $length) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $encoded = Base32::encodeUpper(random_bytes($randomBytesLength));
            $encoded = trim($encoded, '=');
            $encoded = str_replace(['I', 'L', 'O', 'U', '1', '0'], '', $encoded);
            $result .= $encoded;
        }

        return substr($result, 0, $length);
    }

}

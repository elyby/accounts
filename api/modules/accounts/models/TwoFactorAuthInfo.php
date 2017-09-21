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
            'qr' => 'data:image/svg+xml,' . trim($this->drawQrCode($provisioningUri)),
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

    /**
     * otp_secret кодируется в Base32, т.к. после кодирования в результурющей строке нет символов,
     * которые можно перепутать (1 и l, O и 0, и т.д.). Отрицательной стороной является то, что итоговая
     * строка составляет 160% от исходной. Поэтому, генерируя исходный приватный ключ, мы должны обеспечить
     * ему такую длину, чтобы 160% его длины было равно запрошенному значению
     *
     * @param int $length
     *
     * @throws ThisShouldNotHappenException
     */
    private function setOtpSecret(int $length = 24): void {
        $account = $this->getAccount();
        $randomBytesLength = ceil($length / 1.6);
        $randomBase32 = trim(Base32::encodeUpper(random_bytes($randomBytesLength)), '=');
        $account->otp_secret = substr($randomBase32, 0, $length);
        if (!$account->save()) {
            throw new ThisShouldNotHappenException('Cannot set account otp_secret');
        }
    }

    /**
     * В используемой либе для рендеринга QR кода нет возможности указать QR code version.
     * http://www.qrcode.com/en/about/version.html
     * По какой-то причине 7 и 8 версии не читаются вовсе, с логотипом или без.
     * Поэтому нужно иначально привести строку к длинне 9 версии (91), добавляя к концу
     * строки необходимое количество символов "#". Этот символ используется, т.к. нашим
     * контентом является ссылка и чтобы не вводить лишние параметры мы помечаем добавочную
     * часть как хеш часть и все программы для чтения QR кодов продолжают свою работу.
     *
     * @param string $content
     * @return string
     */
    private function forceMinimalQrContentLength(string $content): string {
        return str_pad($content, 91, '#');
    }

}

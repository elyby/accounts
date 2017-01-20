<?php
namespace api\models\profile;

use api\models\base\ApiForm;
use api\validators\TotpValidator;
use api\validators\PasswordRequiredValidator;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\Svg;
use BaconQrCode\Writer;
use Base32\Base32;
use common\components\Qr\ElyDecorator;
use common\helpers\Error as E;
use common\models\Account;
use OTPHP\TOTP;
use yii\base\ErrorException;

class TwoFactorAuthForm extends ApiForm {

    const SCENARIO_ENABLE = 'enable';
    const SCENARIO_DISABLE = 'disable';

    public $token;

    public $password;

    /**
     * @var Account
     */
    private $account;

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

    public function rules() {
        $on = [self::SCENARIO_ENABLE, self::SCENARIO_DISABLE];
        return [
            ['token', 'required', 'message' => E::OTP_TOKEN_REQUIRED, 'on' => $on],
            ['token', TotpValidator::class, 'account' => $this->account, 'window' => 30, 'on' => $on],
            ['password', PasswordRequiredValidator::class, 'account' => $this->account, 'on' => $on],
        ];
    }

    public function getCredentials(): array {
        if (empty($this->account->otp_secret)) {
            $this->setOtpSecret();
        }

        $provisioningUri = $this->getTotp()->getProvisioningUri();

        return [
            'qr' => base64_encode($this->drawQrCode($provisioningUri)),
            'uri' => $provisioningUri,
            'secret' => $this->account->otp_secret,
        ];
    }

    public function getAccount(): Account {
        return $this->account;
    }

    /**
     * @return TOTP
     */
    public function getTotp(): TOTP {
        $totp = new TOTP($this->account->email, $this->account->otp_secret);
        $totp->setIssuer('Ely.by');

        return $totp;
    }

    public function drawQrCode(string $content): string {
        $renderer = new Svg();
        $renderer->setHeight(256);
        $renderer->setWidth(256);
        $renderer->setForegroundColor(new Rgb(32, 126, 92));
        $renderer->setMargin(0);
        $renderer->addDecorator(new ElyDecorator());

        $writer = new Writer($renderer);

        return $writer->writeString($content, Encoder::DEFAULT_BYTE_MODE_ECODING, ErrorCorrectionLevel::H);
    }

    protected function setOtpSecret(): void {
        $this->account->otp_secret = trim(Base32::encode(random_bytes(32)), '=');
        if (!$this->account->save()) {
            throw new ErrorException('Cannot set account otp_secret');
        }
    }

}

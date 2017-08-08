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
use common\components\Qr\ElyDecorator;
use common\helpers\Error as E;
use common\models\Account;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Encoding;
use Yii;
use yii\base\ErrorException;

class TwoFactorAuthForm extends ApiForm {

    const SCENARIO_ACTIVATE = 'enable';
    const SCENARIO_DISABLE = 'disable';

    public $token;

    public $timestamp;

    public $password;

    /**
     * @var Account
     */
    private $account;

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

    public function rules(): array {
        $bothScenarios = [self::SCENARIO_ACTIVATE, self::SCENARIO_DISABLE];
        return [
            ['timestamp', 'integer', 'on' => [self::SCENARIO_ACTIVATE]],
            ['account', 'validateOtpDisabled', 'on' => self::SCENARIO_ACTIVATE],
            ['account', 'validateOtpEnabled', 'on' => self::SCENARIO_DISABLE],
            ['token', 'required', 'message' => E::OTP_TOKEN_REQUIRED, 'on' => $bothScenarios],
            ['token', TotpValidator::class, 'on' => $bothScenarios,
                'account' => $this->account,
                'timestamp' => function() {
                    return $this->timestamp;
                },
            ],
            ['password', PasswordRequiredValidator::class, 'account' => $this->account, 'on' => $bothScenarios],
        ];
    }

    public function getCredentials(): array {
        if (empty($this->account->otp_secret)) {
            $this->setOtpSecret();
        }

        $provisioningUri = $this->getTotp()->getProvisioningUri();

        return [
            'qr' => 'data:image/svg+xml,' . trim($this->drawQrCode($provisioningUri)),
            'uri' => $provisioningUri,
            'secret' => $this->account->otp_secret,
        ];
    }

    public function activate(): bool {
        if ($this->scenario !== self::SCENARIO_ACTIVATE || !$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $account = $this->account;
        $account->is_otp_enabled = true;
        if (!$account->save()) {
            throw new ErrorException('Cannot enable otp for account');
        }

        Yii::$app->user->terminateSessions();

        $transaction->commit();

        return true;
    }

    public function disable(): bool {
        if ($this->scenario !== self::SCENARIO_DISABLE || !$this->validate()) {
            return false;
        }

        $account = $this->account;
        $account->is_otp_enabled = false;
        $account->otp_secret = null;
        if (!$account->save()) {
            throw new ErrorException('Cannot disable otp for account');
        }

        return true;
    }

    public function validateOtpDisabled($attribute) {
        if ($this->account->is_otp_enabled) {
            $this->addError($attribute, E::OTP_ALREADY_ENABLED);
        }
    }

    public function validateOtpEnabled($attribute) {
        if (!$this->account->is_otp_enabled) {
            $this->addError($attribute, E::OTP_NOT_ENABLED);
        }
    }

    public function getAccount(): Account {
        return $this->account;
    }

    /**
     * @return TOTP
     */
    public function getTotp(): TOTP {
        $totp = TOTP::create($this->account->otp_secret);
        $totp->setLabel($this->account->email);
        $totp->setIssuer('Ely.by');

        return $totp;
    }

    public function drawQrCode(string $content): string {
        $content = $this->forceMinimalQrContentLength($content);

        $renderer = new Svg();
        $renderer->setMargin(0);
        $renderer->setForegroundColor(new Rgb(32, 126, 92));
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
     * @throws ErrorException
     */
    protected function setOtpSecret(int $length = 24): void {
        $randomBytesLength = ceil($length / 1.6);
        $randomBase32 = trim(Encoding::base32EncodeUpper(random_bytes($randomBytesLength)), '=');
        $this->account->otp_secret = substr($randomBase32, 0, $length);
        if (!$this->account->save()) {
            throw new ErrorException('Cannot set account otp_secret');
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

<?php
declare(strict_types=1);

namespace api\components\Captcha;

use api\components\Captcha\Providers\ProviderInterface;
use api\components\Captcha\Providers\ReCaptchaEnterprise;
use common\helpers\Error as E;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Yii;
use yii\validators\Validator;

final class CaptchaValidator extends Validator {

    public const string DEFAULT_TYPE = ReCaptchaEnterprise::NAME;

    private const int REPEAT_LIMIT = 3;
    private const int REPEAT_TIMEOUT = 1;

    public $skipOnEmpty = false;

    public $message = E::CAPTCHA_INVALID;

    public string $requiredMessage = E::CAPTCHA_REQUIRED;

    public string $typeMessage = E::CAPTCHA_INVALID;

    /**
     * The name of the model's attribute that contains the captcha provider name
     */
    public string $typeAttribute = 'captchaType';

    public string $defaultType = self::DEFAULT_TYPE;

    public function __construct(
        private readonly CaptchaRegistry $captchaRegistry,
        $config = [],
    ) {
        parent::__construct($config);
    }

    public function validateAttribute($model, $attribute): void {
        $type = $model->{$this->typeAttribute};
        $result = $this->validateCaptcha($model->$attribute, $type);
        if ($result !== null) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        }
    }

    /**
     * @return array{string, array<string, string>}|null
     */
    private function validateCaptcha(mixed $value, mixed $type): ?array {
        if (empty($value) || !is_string($value)) {
            return [$this->requiredMessage, []];
        }

        if (empty($type)) {
            $type = $this->defaultType;
        }

        if (!is_string($type) || !$this->captchaRegistry->hasProvider($type)) {
            return [$this->typeMessage, []];
        }

        $provider = $this->captchaRegistry->getProvider($type);
        if (!$this->verifyWithRetries($provider, $value, Yii::$app->getRequest()->getUserIP())) {
            return [(string)$this->message, []];
        }

        return null;
    }

    private function verifyWithRetries(ProviderInterface $provider, string $token, ?string $remoteIp): bool {
        $repeats = 0;
        while (true) {
            try {
                return $provider->verify($token, $remoteIp);
            } catch (ConnectException|ServerException $e) {
                if (++$repeats >= self::REPEAT_LIMIT) {
                    throw $e;
                }

                sleep(self::REPEAT_TIMEOUT);
            }
        }
    }

}

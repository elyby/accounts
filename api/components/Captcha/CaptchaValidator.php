<?php
declare(strict_types=1);

namespace api\components\Captcha;

use api\components\Captcha\Events\CaptchaEvent;
use api\components\Captcha\Events\CaptchaResultEvent;
use api\components\Captcha\Providers\ReCaptchaEnterprise;
use Carbon\CarbonInterval;
use common\helpers\Error as E;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Yii;
use yii\validators\Validator;

final class CaptchaValidator extends Validator {

    public const string DEFAULT_TYPE = ReCaptchaEnterprise::NAME;

    public const string EVENT_BEFORE_VERIFY = 'beforeVerify';
    public const string EVENT_AFTER_VERIFY = 'afterVerify';

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

        $this->trigger(self::EVENT_BEFORE_VERIFY, new CaptchaEvent($type));

        $remoteIp = Yii::$app->getRequest()->getUserIP();
        $repeats = 0;
        while (true) {
            $startedAt = hrtime(true);
            try {
                $isValid = $provider->verify($value, $remoteIp);
            } catch (ConnectException|ServerException $e) {
                if (++$repeats >= self::REPEAT_LIMIT) {
                    throw $e;
                }

                sleep(self::REPEAT_TIMEOUT);
                continue;
            }

            $duration = CarbonInterval::microseconds(intdiv(hrtime(true) - $startedAt, 1000));
            break;
        }

        $this->trigger(self::EVENT_AFTER_VERIFY, new CaptchaResultEvent($type, $isValid, $duration));

        if (!$isValid) {
            return [(string)$this->message, []];
        }

        return null;
    }

}

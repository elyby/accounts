<?php
namespace api\components\ReCaptcha;


use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class Validator extends \yii\validators\Validator {

    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    const CAPTCHA_RESPONSE_FIELD = 'g-recaptcha-response';

    public $skipOnEmpty = false;

    /**
     * @return Component
     */
    protected function getComponent() {
        return Yii::$app->reCaptcha;
    }

    public function init() {
        parent::init();
        if ($this->getComponent() === null) {
            throw new InvalidConfigException('Required "reCaptcha" component as instance of ' . Component::class . '.');
        }

        if ($this->message === null) {
            $this->message = Yii::t('yii', 'The verification code is incorrect.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value) {
        $value = Yii::$app->request->post(self::CAPTCHA_RESPONSE_FIELD);
        if (empty($value)) {
            return [$this->message, []];
        }

        $requestParams = [
            'secret' => $this->getComponent()->secret,
            'response' => $value,
            'remoteip' => Yii::$app->request->userIP,
        ];

        $requestUrl = self::SITE_VERIFY_URL . '?' . http_build_query($requestParams);
        $response = $this->getResponse($requestUrl);

        if (!isset($response['success'])) {
            throw new Exception('Invalid recaptcha verify response.');
        }

        return $response['success'] ? null : [$this->message, []];
    }

    protected function getResponse($request) {
        $response = file_get_contents($request);

        return json_decode($response, true);
    }

}

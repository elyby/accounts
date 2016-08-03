<?php
namespace api\components\ReCaptcha;

use common\helpers\Error as E;
use GuzzleHttp\Client as GuzzleClient;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class Validator extends \yii\validators\Validator {

    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public $skipOnEmpty = false;

    public $message = E::CAPTCHA_INVALID;

    public $requiredMessage = E::CAPTCHA_REQUIRED;

    public function init() {
        parent::init();
        if ($this->getComponent() === null) {
            throw new InvalidConfigException('Required "reCaptcha" component as instance of ' . Component::class . '.');
        }

        $this->when = function() {
            return !YII_ENV_TEST;
        };
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value) {
        if (empty($value)) {
            return [$this->requiredMessage, []];
        }

        $response = $this->createClient()->post(self::SITE_VERIFY_URL, [
            'form_params' => [
                'secret' => $this->getComponent()->secret,
                'response' => $value,
                'remoteip' => Yii::$app->getRequest()->getUserIP(),
            ],
        ]);
        $data = json_decode($response->getBody(), true);

        if (!isset($data['success'])) {
            throw new Exception('Invalid recaptcha verify response.');
        }

        return $data['success'] ? null : [$this->message, []];
    }

    /**
     * @return Component
     */
    protected function getComponent() {
        return Yii::$app->reCaptcha;
    }

    protected function createClient() {
        return new GuzzleClient();
    }

}

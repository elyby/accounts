<?php
namespace api\components\ReCaptcha;

use common\helpers\Error as E;
use GuzzleHttp\ClientInterface;
use Yii;
use yii\base\Exception;
use yii\di\Instance;

class Validator extends \yii\validators\Validator {

    protected const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public $skipOnEmpty = false;

    public $message = E::CAPTCHA_INVALID;

    public $requiredMessage = E::CAPTCHA_REQUIRED;

    /**
     * @var Component|string
     */
    public $component = 'reCaptcha';

    private $client;

    public function __construct(ClientInterface $client, array $config = []) {
        parent::__construct($config);
        $this->client = $client;
    }

    public function init() {
        parent::init();
        $this->component = Instance::ensure($this->component, Component::class);
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value) {
        if (empty($value)) {
            return [$this->requiredMessage, []];
        }

        $response = $this->client->request('POST', self::SITE_VERIFY_URL, [
            'form_params' => [
                'secret' => $this->component->secret,
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

}

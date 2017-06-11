<?php
namespace api\components\ReCaptcha;

use common\helpers\Error as E;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;
use Yii;
use yii\base\Exception;
use yii\di\Instance;

class Validator extends \yii\validators\Validator {

    private const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private const REPEAT_LIMIT = 3;
    private const REPEAT_TIMEOUT = 1;

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

        $repeats = 0;
        do {
            $isSuccess = true;
            try {
                $response = $this->performRequest($value);
            } catch (ConnectException | ServerException $e) {
                if (++$repeats >= self::REPEAT_LIMIT) {
                    throw $e;
                }

                $isSuccess = false;
                sleep(self::REPEAT_TIMEOUT);
            }
        } while (!$isSuccess);

        /** @noinspection PhpUndefinedVariableInspection */
        $data = json_decode($response->getBody(), true);
        if (!isset($data['success'])) {
            throw new Exception('Invalid recaptcha verify response.');
        }

        if (!$data['success']) {
            return [$this->message, []];
        }

        return null;
    }

    /**
     * @param string $value
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return ResponseInterface
     */
    protected function performRequest(string $value): ResponseInterface {
        return $this->client->request('POST', self::SITE_VERIFY_URL, [
            'form_params' => [
                'secret' => $this->component->secret,
                'response' => $value,
                'remoteip' => Yii::$app->getRequest()->getUserIP(),
            ],
        ]);
    }

}

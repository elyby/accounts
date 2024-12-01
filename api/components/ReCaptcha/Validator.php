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

    private const string SITE_VERIFY_URL = 'https://recaptcha.net/recaptcha/api/siteverify';

    private const int REPEAT_LIMIT = 3;
    private const int REPEAT_TIMEOUT = 1;

    public $skipOnEmpty = false;

    public $message = E::CAPTCHA_INVALID;

    public string $requiredMessage = E::CAPTCHA_REQUIRED;

    public Component|string $component = 'reCaptcha';

    public function __construct(
        private ClientInterface $client,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function init(): void {
        parent::init();
        $this->component = Instance::ensure($this->component, Component::class);
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value): ?array {
        if (empty($value)) {
            return [$this->requiredMessage, []];
        }

        $repeats = 0;
        do {
            $isSuccess = true;
            try {
                $response = $this->performRequest($value);
            } catch (ConnectException|ServerException $e) {
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
     * @throws \GuzzleHttp\Exception\GuzzleException
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

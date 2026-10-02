<?php
declare(strict_types=1);

namespace api\components\Captcha\Providers;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\RequestOptions;
use yii\base\Exception;

/**
 * @see https://yandex.cloud/en/docs/smartcaptcha/concepts/validation
 */
final readonly class YandexSmartCaptcha implements ProviderInterface {

    public const string NAME = 'yandex';

    private const string VALIDATE_URL = 'https://smartcaptcha.cloud.yandex.ru/validate';

    public function __construct(
        private string $serverKey,
        private string $clientKey,
        private GuzzleClientInterface $client,
    ) {
    }

    public function getPublicParams(): array {
        return [
            'publicKey' => $this->clientKey,
        ];
    }

    public function verify(string $token, ?string $remoteIp): bool {
        $params = [
            'secret' => $this->serverKey,
            'token' => $token,
        ];
        if ($remoteIp !== null) {
            $params['ip'] = $remoteIp;
        }

        $response = $this->client->request('POST', self::VALIDATE_URL, [
            RequestOptions::FORM_PARAMS => $params,
        ]);

        $data = json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($data) || !isset($data['status'])) {
            throw new Exception('Invalid Yandex SmartCaptcha validation response.');
        }

        return $data['status'] === 'ok';
    }

}

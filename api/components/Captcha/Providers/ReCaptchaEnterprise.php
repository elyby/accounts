<?php
declare(strict_types=1);

namespace api\components\Captcha\Providers;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\RequestOptions;
use yii\base\Exception;

/**
 * Google reCAPTCHA (Google Cloud edition) with a checkbox key.
 *
 * @see https://docs.cloud.google.com/recaptcha/docs/create-assessment-website
 */
final readonly class ReCaptchaEnterprise implements ProviderInterface {

    public const string NAME = 'recaptcha';

    private const string BASE_URL = 'https://recaptchaenterprise.googleapis.com/v1';

    public function __construct(
        private string $apiKey,
        private string $siteKey,
        private string $projectId,
        private GuzzleClientInterface $client,
    ) {
    }

    public function getPublicParams(): array {
        return [
            'publicKey' => $this->siteKey,
        ];
    }

    public function verify(string $token, ?string $remoteIp): bool {
        $event = [
            'token' => $token,
            'siteKey' => $this->siteKey,
        ];
        if ($remoteIp !== null) {
            $event['userIpAddress'] = $remoteIp;
        }

        $response = $this->client->request('POST', sprintf('%s/projects/%s/assessments', self::BASE_URL, rawurlencode($this->projectId)), [
            RequestOptions::QUERY => [
                'key' => $this->apiKey,
            ],
            RequestOptions::JSON => [
                'event' => $event,
            ],
        ]);

        $data = json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($data) || !isset($data['tokenProperties']) || !is_array($data['tokenProperties'])) {
            throw new Exception('Invalid reCAPTCHA Enterprise assessment response.');
        }

        return ($data['tokenProperties']['valid'] ?? false) === true;
    }

}

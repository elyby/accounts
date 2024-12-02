<?php
declare(strict_types=1);

namespace common\components;

use GuzzleHttp\ClientInterface;
use Webmozart\Assert\Assert;
use Yii;

// TODO: convert to complete Chrly client library
class SkinsSystemApi {

    private ?ClientInterface $client = null;

    public function __construct(private readonly string $baseDomain) {
    }

    /**
     * @param string $username
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array
     */
    public function textures(string $username): ?array {
        $response = $this->getClient()->request('GET', $this->buildUrl('/textures/' . $username));
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $username
     *
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function profile(string $username, bool $signed = false, ?string $fallbackUuid = null): ?array {
        $query = [];
        if ($signed) {
            $query['unsigned'] = 'false';
        }

        if ($fallbackUuid !== null) {
            $query['onUnknownProfileRespondWithUuid'] = $fallbackUuid;
        }

        $url = "/profile/{$username}" . (empty($query) ? '' : ('?' . http_build_query($query)));
        $response = $this->getClient()->request('GET', $this->buildUrl($url));
        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param 'pem'|'der' $format
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSignatureVerificationKey(string $format = 'pem'): string {
        Assert::inArray($format, ['pem', 'der']);

        return $this->getClient()
            ->request('GET', $this->buildUrl("/signature-verification-key.{$format}"))
            ->getBody()
            ->getContents();
    }

    public function setClient(ClientInterface $client): void {
        $this->client = $client;
    }

    private function buildUrl(string $url): string {
        return $this->baseDomain . $url;
    }

    private function getClient(): ClientInterface {
        if ($this->client === null) {
            $this->client = Yii::$container->get(ClientInterface::class);
        }

        return $this->client;
    }

}

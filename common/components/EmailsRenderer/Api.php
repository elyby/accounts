<?php
declare(strict_types=1);

namespace common\components\EmailsRenderer;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;

class Api {

    private $baseUrl;

    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(string $baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    public function setClient(ClientInterface $client): void {
        $this->client = $client;
    }

    /**
     * @param \common\components\EmailsRenderer\Request\TemplateRequest $request
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTemplate(Request\TemplateRequest $request): string {
        return $this->getClient()
            ->request('GET', "/templates/{$request->getLocale()}/{$request->getName()}", [
                'query' => $request->getParams(),
            ])
            ->getBody()
            ->getContents();
    }

    /**
     * @return ClientInterface
     */
    protected function getClient(): ClientInterface {
        if ($this->client === null) {
            $this->client = $this->createDefaultClient();
        }

        return $this->client;
    }

    private function createDefaultClient(): ClientInterface {
        return new GuzzleClient([
            'timeout' => 5,
            'base_uri' => $this->baseUrl,
        ]);
    }

}

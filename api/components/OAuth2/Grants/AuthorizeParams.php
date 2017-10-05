<?php
namespace api\components\OAuth2\Grants;

use api\components\OAuth2\Entities\ClientEntity;

class AuthorizeParams {

    private $client;

    private $redirectUri;

    private $state;

    private $responseType;

    /**
     * @var \api\components\OAuth2\Entities\ScopeEntity[]
     */
    private $scopes;

    public function __construct(
        ClientEntity $client,
        string $redirectUri,
        ?string $state,
        string $responseType,
        array $scopes
    ) {
        $this->client = $client;
        $this->redirectUri = $redirectUri;
        $this->state = $state;
        $this->responseType = $responseType;
        $this->scopes = $scopes;
    }

    public function getClient(): ClientEntity {
        return $this->client;
    }

    public function getRedirectUri(): string {
        return $this->redirectUri;
    }

    public function getState(): ?string {
        return $this->state;
    }

    public function getResponseType(): string {
        return $this->responseType;
    }

    /**
     * @return \api\components\OAuth2\Entities\ScopeEntity[]
     */
    public function getScopes(): array {
        return $this->scopes ?? [];
    }

}

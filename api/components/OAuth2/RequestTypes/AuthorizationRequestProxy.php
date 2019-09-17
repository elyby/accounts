<?php
declare(strict_types=1);

namespace api\components\OAuth2\RequestTypes;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

class AuthorizationRequestProxy extends AuthorizationRequest {

    /**
     * @var AuthorizationRequest
     */
    private $authorizationRequest;

    public function __construct(AuthorizationRequest $authorizationRequest) {
        $this->authorizationRequest = $authorizationRequest;
    }

    public function getOriginalAuthorizationRequest(): AuthorizationRequest {
        return $this->authorizationRequest;
    }

    public function getGrantTypeId(): string {
        return $this->authorizationRequest->getGrantTypeId();
    }

    public function setGrantTypeId($grantTypeId): void {
        $this->authorizationRequest->setGrantTypeId($grantTypeId);
    }

    public function getClient(): ClientEntityInterface {
        return $this->authorizationRequest->getClient();
    }

    public function setClient(ClientEntityInterface $client): void {
        $this->authorizationRequest->setClient($client);
    }

    public function getUser(): UserEntityInterface {
        return $this->authorizationRequest->getUser();
    }

    public function setUser(UserEntityInterface $user): void {
        $this->authorizationRequest->setUser($user);
    }

    public function getScopes(): array {
        return $this->authorizationRequest->getScopes();
    }

    public function setScopes(array $scopes): void {
        $this->authorizationRequest->setScopes($scopes);
    }

    public function isAuthorizationApproved(): bool {
        return $this->authorizationRequest->isAuthorizationApproved();
    }

    public function setAuthorizationApproved($authorizationApproved): void {
        $this->authorizationRequest->setAuthorizationApproved($authorizationApproved);
    }

    public function getRedirectUri(): ?string {
        return $this->authorizationRequest->getRedirectUri();
    }

    public function setRedirectUri($redirectUri): void {
        $this->authorizationRequest->setRedirectUri($redirectUri);
    }

    public function getState(): ?string {
        return $this->authorizationRequest->getState();
    }

    public function setState($state): void {
        $this->authorizationRequest->setState($state);
    }

    public function getCodeChallenge(): string {
        return $this->authorizationRequest->getCodeChallenge();
    }

    public function setCodeChallenge($codeChallenge): void {
        $this->authorizationRequest->setCodeChallenge($codeChallenge);
    }

    public function getCodeChallengeMethod(): string {
        return $this->authorizationRequest->getCodeChallengeMethod();
    }

    public function setCodeChallengeMethod($codeChallengeMethod): void {
        $this->authorizationRequest->setCodeChallengeMethod($codeChallengeMethod);
    }

}

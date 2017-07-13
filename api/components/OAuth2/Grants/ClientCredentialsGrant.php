<?php
namespace api\components\OAuth2\Grants;

use api\components\OAuth2\Entities;
use League\OAuth2\Server\Entity\ClientEntity;

class ClientCredentialsGrant extends \League\OAuth2\Server\Grant\ClientCredentialsGrant {

    protected function createAccessTokenEntity() {
        return new Entities\AccessTokenEntity($this->server);
    }

    protected function createRefreshTokenEntity() {
        return new Entities\RefreshTokenEntity($this->server);
    }

    protected function createSessionEntity() {
        return new Entities\SessionEntity($this->server);
    }

    /**
     * По стандарту OAuth2 scopes должны разделяться пробелом, а не запятой. Косяк.
     * Так что оборачиваем функцию разбора скоупов, заменяя пробелы на запятые.
     *
     * @param string       $scopeParam
     * @param ClientEntity $client
     * @param string $redirectUri
     *
     * @return \League\OAuth2\Server\Entity\ScopeEntity[]
     */
    public function validateScopes($scopeParam = '', ClientEntity $client, $redirectUri = null) {
        $scopes = str_replace(' ', $this->server->getScopeDelimiter(), $scopeParam);
        return parent::validateScopes($scopes, $client, $redirectUri);
    }

}

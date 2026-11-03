<?php
declare(strict_types=1);

namespace api\modules\oauth\controllers;

use api\components\Tokens\TokenReader;
use api\components\User\JwtIdentity;
use api\controllers\Controller;
use common\components\OAuth2\OpenIdClaims;
use common\components\OAuth2\Repositories\PublicScopeRepository;
use common\components\OAuth2\ScopesMapper;
use Yii;

final class UserInfoController extends Controller {

    /**
     * @return array<string, list<string>>
     */
    public function verbs(): array {
        return [
            // Both methods must be supported (OpenID Connect Core 1.0 section 5.3.1)
            'index' => ['GET', 'POST'],
        ];
    }

    /**
     * OpenID Connect UserInfo endpoint (OpenID Connect Core 1.0 section 5.3).
     */
    public function actionIndex(): array {
        $identity = Yii::$app->user->identity;
        $account = $identity?->getAccount();
        // The token must be issued to the OAuth client on behalf of the user
        if (!$identity instanceof JwtIdentity || $account === null || (new TokenReader($identity->getToken()))->getClientId() === null) {
            return $this->buildErrorResponse(401, 'invalid_token', 'The access token is invalid.');
        }

        $scopes = ScopesMapper::normalizeScopes(array_filter($identity->getAssignedPermissions(), fn(string $scope): bool => $scope !== ''));
        if (!in_array(PublicScopeRepository::OPENID, $scopes, true)) {
            return $this->buildErrorResponse(403, 'insufficient_scope', 'The access token must contain the `openid` scope.');
        }

        return OpenIdClaims::build($account, $scopes);
    }

    /**
     * The error response for the protected resource (RFC 6750 section 3.1)
     *
     * @return array<string, string>
     */
    private function buildErrorResponse(int $statusCode, string $error, string $description): array {
        $response = Yii::$app->response;
        $response->setStatusCode($statusCode);
        $response->headers->set('WWW-Authenticate', sprintf('Bearer error="%s", error_description="%s"', $error, $description));

        return [
            'error' => $error,
            'error_description' => $description,
        ];
    }

}

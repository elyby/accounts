<?php
declare(strict_types=1);

namespace api\modules\oauth\controllers;

use api\filters\NginxCache;
use common\components\OAuth2\OpenIdClaims;
use common\components\OAuth2\Repositories\PublicScopeRepository;
use Yii;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

final class DiscoveryController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            // The document must be available for the browser-based applications (RFC 8414 section 3).
            // The filter handles the preflight requests
            'corsFilter' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => false,
                    'Access-Control-Max-Age' => 86400,
                ],
            ],
            'nginxCache' => [
                'class' => NginxCache::class,
                'rules' => [
                    'authorization-server' => 3600, // 1h
                    'openid-configuration' => 3600, // 1h
                    'jwks' => 3600, // 1h
                ],
            ],
        ]);
    }

    /**
     * OAuth 2.0 Authorization Server Metadata (RFC 8414).
     */
    public function actionAuthorizationServer(): array {
        return $this->buildMetadata();
    }

    /**
     * OpenID Connect Discovery (OpenID Connect Discovery 1.0 section 3).
     */
    public function actionOpenidConfiguration(): array {
        $metadata = $this->buildMetadata();
        $issuer = $metadata['issuer'];

        return array_merge($metadata, [
            'userinfo_endpoint' => "{$issuer}/api/oauth2/v1/userinfo",
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['ES256'],
            'claims_supported' => array_merge(OpenIdClaims::SUPPORTED_CLAIMS, ['iss', 'aud', 'exp', 'iat', 'nonce']),
            // The request objects aren't supported, but the request_uri_parameter_supported is true by default,
            // so both values are need to be set explicitly
            'request_parameter_supported' => false,
            'request_uri_parameter_supported' => false,
        ]);
    }

    /**
     * JSON Web Key Set (RFC 7517) with the public keys to verify the issued tokens signature.
     */
    public function actionJwks(): array {
        $response = Yii::$app->response;
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        // See the comment in the buildMetadata()
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return Yii::$app->tokens->getJwks();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetadata(): array {
        $response = Yii::$app->response;
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        // The Cors filter adds this header only when the Origin header is passed, but the response is cached by nginx
        // regardless of the Origin, so the header must always be present
        $response->headers->set('Access-Control-Allow-Origin', '*');

        $issuer = Yii::$app->request->getHostInfo();
        $clientAuthMethods = ['client_secret_post', 'client_secret_basic', 'none'];

        return [
            'issuer' => $issuer,
            'authorization_endpoint' => "{$issuer}/oauth2/v1",
            'token_endpoint' => "{$issuer}/api/oauth2/v1/token",
            'device_authorization_endpoint' => "{$issuer}/api/oauth2/v1/devicecode",
            'revocation_endpoint' => "{$issuer}/api/oauth2/v1/revoke",
            'jwks_uri' => "{$issuer}/.well-known/jwks.json",
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => [
                'authorization_code',
                'refresh_token',
                'client_credentials',
                'urn:ietf:params:oauth:grant-type:device_code',
            ],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => $clientAuthMethods,
            'revocation_endpoint_auth_methods_supported' => $clientAuthMethods,
            // The scopes for the client_credentials grant are internal, so they aren't published
            'scopes_supported' => PublicScopeRepository::ALLOWED_SCOPES,
            'service_documentation' => 'https://docs.ely.by/en/oauth.html',
        ];
    }

}

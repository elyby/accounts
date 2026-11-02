<?php
declare(strict_types=1);

namespace api\modules\oauth\controllers;

use api\filters\NginxCache;
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
                ],
            ],
        ]);
    }

    /**
     * OAuth 2.0 Authorization Server Metadata (RFC 8414).
     *
     * The URLs are built from the current host, since the issuer must match the URL
     * that was used to retrieve the document (RFC 8414 section 3.3).
     */
    public function actionAuthorizationServer(): array {
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

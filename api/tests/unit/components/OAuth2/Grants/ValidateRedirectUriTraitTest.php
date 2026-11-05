<?php
declare(strict_types=1);

namespace api\tests\unit\components\OAuth2\Grants;

use api\tests\unit\TestCase;
use common\components\OAuth2\Entities\ClientEntity;
use common\components\OAuth2\Grants\ValidateRedirectUriTrait;
use common\models\OauthClient;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\EventEmitting\EventEmitter;
use League\OAuth2\Server\Exception\OAuthServerException;

final class ValidateRedirectUriTraitTest extends TestCase {

    public function testWebApplication(): void {
        $client = $this->createClient(OauthClient::TYPE_WEB_APPLICATION, 'http://ely.by');

        $this->assertValidation($client, 'http://ely.by/callback', true);
        $this->assertValidation($client, 'http://evil.com', false);
    }

    public function testWebApplicationWithoutRegisteredRedirectUri(): void {
        $client = $this->createClient(OauthClient::TYPE_WEB_APPLICATION, '');

        $this->assertValidation($client, 'https://example.com/callback', true);
        $this->assertValidation($client, 'javascript:alert(1)', false);
    }

    public function testDesktopApplication(): void {
        $client = $this->createClient(OauthClient::TYPE_DESKTOP_APPLICATION, '');

        $this->assertValidation($client, 'http://127.0.0.1:51234/callback', true);
        $this->assertValidation($client, 'http://example.com/callback', false);
    }

    /**
     * @phpstan-param OauthClient::TYPE_* $type
     */
    private function createClient(string $type, string $registeredUri): ClientEntity {
        $client = new OauthClient();
        $client->type = $type;

        return new ClientEntity('client', 'Client', $registeredUri, model: $client);
    }

    private function assertValidation(ClientEntity $client, string $redirectUri, bool $expected): void {
        $validator = new class {
            use ValidateRedirectUriTrait {
                validateRedirectUri as public;
            }

            public function getEmitter(): EventEmitter {
                return new EventEmitter();
            }
        };

        try {
            $validator->validateRedirectUri($redirectUri, $client, new ServerRequest('GET', 'http://localhost'));
            $isValid = true;
        } catch (OAuthServerException $e) {
            $this->assertSame('invalid_request', $e->getErrorType());
            $this->assertFalse($e->hasRedirect(), 'The error must not be redirected to the invalid redirect uri');
            $isValid = false;
        }

        $this->assertSame($expected, $isValid, "Redirect uri \"{$redirectUri}\" validation result is unexpected");
    }

}

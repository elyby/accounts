<?php
declare(strict_types=1);

namespace api\tests\unit\components\Tokens;

use api\components\Tokens\TokensFactory;
use api\tests\unit\TestCase;
use Carbon\Carbon;
use common\models\Account;
use common\models\AccountSession;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class TokensFactoryTest extends TestCase {

    public function testCreateForAccount(): void {
        $factory = new TokensFactory();

        $account = new Account();
        $account->id = 1;

        // Create for account

        $token = $factory->createForWebAccount($account);
        $this->assertEqualsWithDelta(time(), $token->claims()->get('iat')->getTimestamp(), 1);
        $this->assertEqualsWithDelta(time() + 60 * 60 * 24 * 7, $token->claims()->get('exp')->getTimestamp(), 2);
        $this->assertSame('ely|1', $token->claims()->get('sub'));
        $this->assertSame('accounts_web_user', $token->claims()->get('scope'));
        $this->assertArrayNotHasKey('jti', $token->claims()->all());

        $session = new AccountSession();
        $session->id = 2;

        // Create for account with remember me

        $token = $factory->createForWebAccount($account, $session);
        $this->assertEqualsWithDelta(time(), $token->claims()->get('iat')->getTimestamp(), 1);
        $this->assertEqualsWithDelta(time() + 3600, $token->claims()->get('exp')->getTimestamp(), 2);
        $this->assertSame('ely|1', $token->claims()->get('sub'));
        $this->assertSame('accounts_web_user', $token->claims()->get('scope'));
        $this->assertSame(2, (int)$token->claims()->get('jti'));
    }

    public function testCreateForOauthClient(): void {
        $factory = new TokensFactory();

        $client = $this->createMock(ClientEntityInterface::class);
        $client->method('getIdentifier')->willReturn('clientId');

        $scope1 = $this->createMock(ScopeEntityInterface::class);
        $scope1->method('getIdentifier')->willReturn('scope1');
        $scope2 = $this->createMock(ScopeEntityInterface::class);
        $scope2->method('getIdentifier')->willReturn('scope2');

        $expiryDateTime = Carbon::now()->addDay()->toDateTimeImmutable();

        // Create for auth code grant

        $accessToken = $this->createMock(AccessTokenEntityInterface::class);
        $accessToken->method('getClient')->willReturn($client);
        $accessToken->method('getScopes')->willReturn([$scope1, $scope2]);
        $accessToken->method('getExpiryDateTime')->willReturn($expiryDateTime);
        $accessToken->method('getUserIdentifier')->willReturn('1');

        $token = $factory->createForOAuthClient($accessToken);
        $this->assertEqualsWithDelta(time(), $token->claims()->get('iat')->getTimestamp(), 1);
        $this->assertEqualsWithDelta($expiryDateTime->getTimestamp(), $token->claims()->get('exp')->getTimestamp(), 2);
        $this->assertSame('ely|1', $token->claims()->get('sub'));
        $this->assertSame('clientId', $token->claims()->get('client_id'));
        $this->assertSame('scope1 scope2', $token->claims()->get('scope'));

        // Create for client credentials grant

        $accessToken = $this->createMock(AccessTokenEntityInterface::class);
        $accessToken->method('getClient')->willReturn($client);
        $accessToken->method('getScopes')->willReturn([$scope1, $scope2]);
        $accessToken->method('getExpiryDateTime')->willReturn(Carbon::now()->subDay()->toDateTimeImmutable());
        $accessToken->method('getUserIdentifier')->willReturn(null);

        $token = $factory->createForOAuthClient($accessToken);
        $this->assertSame('no value', $token->claims()->get('exp', 'no value'));
        $this->assertSame('no value', $token->claims()->get('sub', 'no value'));
    }

    public function testCreateForMinecraftAccount(): void {
        $factory = new TokensFactory();

        $account = new Account();
        $account->id = 1;
        $clientToken = 'e44fae79-f80e-4975-952e-47e8a9ed9472';

        $token = $factory->createForMinecraftAccount($account, $clientToken);
        $this->assertEqualsWithDelta(time(), $token->claims()->get('iat')->getTimestamp(), 5);
        $this->assertEqualsWithDelta(time() + 60 * 60 * 24 * 2, $token->claims()->get('exp')->getTimestamp(), 5);
        $this->assertSame('obtain_own_account_info minecraft_server_session', $token->claims()->get('scope'));
        $this->assertNotSame('e44fae79-f80e-4975-952e-47e8a9ed9472', $token->claims()->get('ely-client-token'));
        $this->assertSame('ely|1', $token->claims()->get('sub'));
    }

}

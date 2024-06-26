<?php
declare(strict_types=1);

namespace codeception\api\unit\components\User;

use api\components\User\Component;
use api\components\User\JwtIdentity;
use api\components\User\LegacyOAuth2Identity;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\AccountSession;
use common\models\OauthClient;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\AccountSessionFixture;
use common\tests\fixtures\OauthClientFixture;
use common\tests\fixtures\OauthSessionFixture;
use Lcobucci\JWT\Claim\Basic;
use Lcobucci\JWT\Token;

class ComponentTest extends TestCase {

    /**
     * @var Component|\PHPUnit\Framework\MockObject\MockObject
     */
    private $component;

    public function _before() {
        parent::_before();
        $this->component = new Component();
    }

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'sessions' => AccountSessionFixture::class,
            'oauthClients' => OauthClientFixture::class,
            'oauthSessions' => OauthSessionFixture::class,
        ];
    }

    public function testGetActiveSession() {
        // User is guest
        $component = new Component();
        $this->assertNull($component->getActiveSession());

        // Identity is a Oauth2Identity
        $component->setIdentity($this->createMock(LegacyOAuth2Identity::class));
        $this->assertNull($component->getActiveSession());

        // Identity is correct, but have no jti claim
        $identity = $this->createMock(JwtIdentity::class);
        $identity->method('getToken')->willReturn(new Token());
        $component->setIdentity($identity);
        $this->assertNull($component->getActiveSession());

        // Identity is correct and has jti claim, but there is no associated session
        $identity = $this->createMock(JwtIdentity::class);
        $identity->method('getToken')->willReturn(new Token([], ['jti' => new Basic('jti', 999999)]));
        $component->setIdentity($identity);
        $this->assertNull($component->getActiveSession());

        // Identity is correct, has jti claim and associated session exists
        $identity = $this->createMock(JwtIdentity::class);
        $identity->method('getToken')->willReturn(new Token([], ['jti' => new Basic('jti', 1)]));
        $component->setIdentity($identity);
        $session = $component->getActiveSession();
        $this->assertNotNull($session);
        $this->assertSame(1, $session->id);
    }

    public function testTerminateSessions() {
        /** @var AccountSession $session */
        $session = $this->tester->grabFixture('sessions', 'admin2');

        $component = $this->createPartialMock(Component::class, ['getActiveSession']);
        $component->expects($this->once())->method('getActiveSession')->willReturn($session);

        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');

        // Dry run: no sessions should be removed
        $component->terminateSessions($account, Component::KEEP_MINECRAFT_SESSIONS | Component::KEEP_SITE_SESSIONS);
        $this->assertNotEmpty($account->getSessions()->all());

        // All Minecraft sessions should be removed. Web sessions should be kept
        $component->terminateSessions($account, Component::KEEP_SITE_SESSIONS);
        $this->assertNotEmpty($account->getSessions()->all());
        $this->assertEqualsWithDelta(time(), $account->getOauthSessions()->andWhere(['client_id' => OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER])->one()->revoked_at, 5);

        // All sessions should be removed except the current one
        $component->terminateSessions($account, Component::KEEP_CURRENT_SESSION);
        $sessions = $account->getSessions()->all();
        $this->assertCount(1, $sessions);
        $this->assertSame($session->id, $sessions[0]->id);

        // With no arguments each and every session should be removed
        $component->terminateSessions($account);
        $this->assertEmpty($account->getSessions()->all());
    }

}

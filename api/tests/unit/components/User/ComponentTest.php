<?php
declare(strict_types=1);

namespace codeception\api\unit\components\User;

use api\components\User\Component;
use api\components\User\JwtIdentity;
use api\components\User\OAuth2Identity;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\AccountSession;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\AccountSessionFixture;
use common\tests\fixtures\MinecraftAccessKeyFixture;
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
            'minecraftSessions' => MinecraftAccessKeyFixture::class,
        ];
    }

    public function testGetActiveSession() {
        // User is guest
        $component = new Component();
        $this->assertNull($component->getActiveSession());

        // Identity is a Oauth2Identity
        $component->setIdentity(mock(OAuth2Identity::class));
        $this->assertNull($component->getActiveSession());

        // Identity is correct, but have no jti claim
        /** @var JwtIdentity|\Mockery\MockInterface $identity */
        $identity = mock(JwtIdentity::class);
        $identity->shouldReceive('getToken')->andReturn(new Token());
        $component->setIdentity($identity);
        $this->assertNull($component->getActiveSession());

        // Identity is correct and has jti claim, but there is no associated session
        /** @var JwtIdentity|\Mockery\MockInterface $identity */
        $identity = mock(JwtIdentity::class);
        $identity->shouldReceive('getToken')->andReturn(new Token([], ['jti' => new Basic('jti', 999999)]));
        $component->setIdentity($identity);
        $this->assertNull($component->getActiveSession());

        // Identity is correct, has jti claim and associated session exists
        /** @var JwtIdentity|\Mockery\MockInterface $identity */
        $identity = mock(JwtIdentity::class);
        $identity->shouldReceive('getToken')->andReturn(new Token([], ['jti' => new Basic('jti', 1)]));
        $component->setIdentity($identity);
        $session = $component->getActiveSession();
        $this->assertNotNull($session);
        $this->assertSame(1, $session->id);
    }

    public function testTerminateSessions() {
        /** @var AccountSession $session */
        $session = $this->tester->grabFixture('sessions', 'admin2');

        /** @var Component|\Mockery\MockInterface $component */
        $component = mock(Component::class . '[getActiveSession]')->makePartial();
        $component->shouldReceive('getActiveSession')->times(1)->andReturn($session);

        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');

        // Dry run: no sessions should be removed
        $component->terminateSessions($account, Component::KEEP_MINECRAFT_SESSIONS | Component::KEEP_SITE_SESSIONS);
        $this->assertNotEmpty($account->getMinecraftAccessKeys()->all());
        $this->assertNotEmpty($account->getSessions()->all());

        // All Minecraft sessions should be removed. Web sessions should be kept
        $component->terminateSessions($account, Component::KEEP_SITE_SESSIONS);
        $this->assertEmpty($account->getMinecraftAccessKeys()->all());
        $this->assertNotEmpty($account->getSessions()->all());

        // All sessions should be removed except the current one
        $component->terminateSessions($account, Component::KEEP_CURRENT_SESSION);
        $sessions = $account->getSessions()->all();
        $this->assertCount(1, $sessions);
        $this->assertSame($session->id, $sessions[0]->id);

        // With no arguments each and every session should be removed
        $component->terminateSessions($account);
        $this->assertEmpty($account->getSessions()->all());
        $this->assertEmpty($account->getMinecraftAccessKeys()->all());
    }

}

<?php
declare(strict_types=1);

namespace api\tests\unit\components\User;

use api\components\User\Component;
use api\components\User\JwtIdentity;
use api\components\User\LegacyOAuth2Identity;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\AccountSession;
use common\models\OauthClient;
use common\models\OauthSession;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\AccountSessionFixture;
use common\tests\fixtures\OauthClientFixture;
use common\tests\fixtures\OauthSessionFixture;
use DateTimeImmutable;
use Lcobucci\JWT\Builder as BuilderInterface;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Blake2b;
use Lcobucci\JWT\Signer\Key;

class ComponentTest extends TestCase {

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'sessions' => AccountSessionFixture::class,
            'oauthClients' => OauthClientFixture::class,
            'oauthSessions' => OauthSessionFixture::class,
        ];
    }

    public function testGetActiveSession(): void {
        // User is guest
        $component = new Component();
        $this->assertNull($component->getActiveSession());

        // Identity is a Oauth2Identity
        $component->setIdentity($this->createMock(LegacyOAuth2Identity::class));
        $this->assertNull($component->getActiveSession());

        // Identity is correct, but have no jti claim
        $identity = $this->createMock(JwtIdentity::class);
        $identity
            ->method('getToken')
            ->willReturn(
                (new JwtFacade())
                    ->issue(
                        new Blake2b(),
                        Key\InMemory::plainText('MpQd6dDPiqnzFSWmpUfLy4+Rdls90Ca4C8e0QD0IxqY='),
                        static fn(BuilderInterface $builder, DateTimeImmutable $issuedAt): \Lcobucci\JWT\Builder => $builder,
                    ),
            );
        $component->setIdentity($identity);
        $this->assertNull($component->getActiveSession());

        // Identity is correct and has jti claim, but there is no associated session
        $identity = $this->createMock(JwtIdentity::class);
        $identity
            ->method('getToken')
            ->willReturn(
                (new JwtFacade())
                    ->issue(
                        new Blake2b(),
                        Key\InMemory::plainText('MpQd6dDPiqnzFSWmpUfLy4+Rdls90Ca4C8e0QD0IxqY='),
                        static fn(BuilderInterface $builder, DateTimeImmutable $issuedAt): \Lcobucci\JWT\Builder => $builder->identifiedBy('999999'),
                    ),
            );
        $component->setIdentity($identity);
        $this->assertNull($component->getActiveSession());

        // Identity is correct, has jti claim and associated session exists
        $identity = $this->createMock(JwtIdentity::class);
        $identity
            ->method('getToken')
            ->willReturn(
                (new JwtFacade())
                    ->issue(
                        new Blake2b(),
                        Key\InMemory::plainText('MpQd6dDPiqnzFSWmpUfLy4+Rdls90Ca4C8e0QD0IxqY='),
                        static fn(BuilderInterface $builder, DateTimeImmutable $issuedAt): \Lcobucci\JWT\Builder => $builder->identifiedBy('1'),
                    ),
            );
        $component->setIdentity($identity);
        $session = $component->getActiveSession();
        // @phpstan-ignore method.impossibleType (it is possible since we're changing identity via setIdentity() method)
        $this->assertNotNull($session);
        $this->assertSame(1, $session->id);
    }

    public function testTerminateSessions(): void {
        /** @var AccountSession $session */
        $session = $this->tester->grabFixture('sessions', 'admin2');

        $component = $this->createPartialMock(Component::class, ['getActiveSession']);
        $component->expects($this->once())->method('getActiveSession')->willReturn($session);

        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');

        $oauthSessionsQuery = $account->getOauthSessions()->andWhere(['NOT', ['client_id' => OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER]]);
        $oauthSessionsCount = (int)$oauthSessionsQuery->count();
        $this->assertGreaterThan(0, $oauthSessionsCount);
        $minecraftSessionQuery = $account->getOauthSessions()->andWhere(['client_id' => OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER]);
        $minecraftSessionRevokedAt = $minecraftSessionQuery->one()->revoked_at;

        // Dry run: no sessions should be removed
        $component->terminateSessions($account, Component::KEEP_MINECRAFT_SESSIONS | Component::KEEP_SITE_SESSIONS | Component::KEEP_OAUTH_SESSIONS);
        $this->assertNotEmpty($account->getSessions()->all());
        $this->assertFalse((clone $oauthSessionsQuery)->andWhere(['>=', 'revoked_at', time() - 5])->exists());

        // OAuth tokens should be revoked, but the sessions (approved clients and scopes) are kept.
        // Minecraft session should be kept
        $component->terminateSessions($account, Component::KEEP_MINECRAFT_SESSIONS | Component::KEEP_SITE_SESSIONS);
        /** @var OauthSession[] $oauthSessions */
        $oauthSessions = (clone $oauthSessionsQuery)->all();
        $this->assertCount($oauthSessionsCount, $oauthSessions);
        foreach ($oauthSessions as $oauthSession) {
            $this->assertEqualsWithDelta(time(), $oauthSession->revoked_at, 5);
        }

        $this->assertSame($minecraftSessionRevokedAt, $minecraftSessionQuery->one()->revoked_at);

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

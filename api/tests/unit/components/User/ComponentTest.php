<?php
declare(strict_types=1);

namespace codeception\api\unit\components\User;

use api\components\User\Component;
use api\components\User\Identity;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\AccountSession;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\AccountSessionFixture;
use common\tests\fixtures\MinecraftAccessKeyFixture;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Jwt;
use Yii;
use yii\web\Request;

class ComponentTest extends TestCase {

    /**
     * @var Component|\PHPUnit\Framework\MockObject\MockObject
     */
    private $component;

    public function _before() {
        parent::_before();
        $this->component = new Component($this->getComponentConfig());
    }

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'sessions' => AccountSessionFixture::class,
            'minecraftSessions' => MinecraftAccessKeyFixture::class,
        ];
    }

    public function testCreateJwtAuthenticationToken() {
        $this->mockRequest();

        // Token without session
        $account = new Account(['id' => 1]);
        $token = $this->component->createJwtAuthenticationToken($account);
        $payloads = $token->getPayload();
        $this->assertEqualsWithDelta(time(), $payloads->findClaimByName('iat')->getValue(), 3);
        $this->assertEqualsWithDelta(time() + 60 * 60 * 24 * 7, $payloads->findClaimByName('exp')->getValue(), 3);
        $this->assertSame('ely|1', $payloads->findClaimByName('sub')->getValue());
        $this->assertSame('accounts_web_user', $payloads->findClaimByName('ely-scopes')->getValue());
        $this->assertNull($payloads->findClaimByName('jti'));

        $session = new AccountSession(['id' => 2]);
        $token = $this->component->createJwtAuthenticationToken($account, $session);
        $payloads = $token->getPayload();
        $this->assertEqualsWithDelta(time(), $payloads->findClaimByName('iat')->getValue(), 3);
        $this->assertEqualsWithDelta(time() + 3600, $payloads->findClaimByName('exp')->getValue(), 3);
        $this->assertSame('ely|1', $payloads->findClaimByName('sub')->getValue());
        $this->assertSame('accounts_web_user', $payloads->findClaimByName('ely-scopes')->getValue());
        $this->assertSame(2, $payloads->findClaimByName('jti')->getValue());
    }

    public function testRenewJwtAuthenticationToken() {
        $userIP = '192.168.0.1';
        $this->mockRequest($userIP);
        /** @var AccountSession $session */
        $session = $this->tester->grabFixture('sessions', 'admin');
        $result = $this->component->renewJwtAuthenticationToken($session);
        $this->assertSame($session, $result->getSession());
        $this->assertSame($session->account_id, $result->getAccount()->id);
        $session->refresh(); // reload data from db
        $this->assertEqualsWithDelta(time(), $session->last_refreshed_at, 3);
        $this->assertSame($userIP, $session->getReadableIp());
        $payloads = (new Jwt())->deserialize($result->getJwt())->getPayload();
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEqualsWithDelta(time(), $payloads->findClaimByName(Claim\IssuedAt::NAME)->getValue(), 3);
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEqualsWithDelta(time() + 3600, $payloads->findClaimByName('exp')->getValue(), 3);
        /** @noinspection NullPointerExceptionInspection */
        $this->assertSame('ely|1', $payloads->findClaimByName('sub')->getValue());
        /** @noinspection NullPointerExceptionInspection */
        $this->assertSame('accounts_web_user', $payloads->findClaimByName('ely-scopes')->getValue());
        /** @noinspection NullPointerExceptionInspection */
        $this->assertSame($session->id, $payloads->findClaimByName('jti')->getValue(), 'session has not changed');
    }

    public function testParseToken() {
        $this->mockRequest();
        $account = new Account(['id' => 1]);
        $token = $this->component->createJwtAuthenticationToken($account);
        $jwt = $this->component->serializeToken($token);
        $this->component->parseToken($jwt);
    }

    public function testGetActiveSession() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        /** @var AccountSession $session */
        $session = $this->tester->grabFixture('sessions', 'admin');
        $token = $this->component->createJwtAuthenticationToken($account, $session);
        $jwt = $this->component->serializeToken($token);

        /** @var Component|\PHPUnit\Framework\MockObject\MockObject $component */
        $component = $this->getMockBuilder(Component::class)
            ->setMethods(['getIsGuest'])
            ->setConstructorArgs([$this->getComponentConfig()])
            ->getMock();

        $component
            ->method('getIsGuest')
            ->willReturn(false);

        $this->mockAuthorizationHeader($jwt);

        $foundSession = $component->getActiveSession();
        $this->assertInstanceOf(AccountSession::class, $foundSession);
        $this->assertSame($session->id, $foundSession->id);
    }

    public function testTerminateSessions() {
        /** @var AccountSession $session */
        $session = $this->tester->grabFixture('sessions', 'admin2');

        /** @var Component|\Mockery\MockInterface $component */
        $component = mock(Component::class . '[getActiveSession]', [$this->getComponentConfig()])->makePartial();
        $component->shouldReceive('getActiveSession')->times(1)->andReturn($session);

        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $component->createJwtAuthenticationToken($account);

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

    private function mockRequest($userIP = '127.0.0.1') {
        /** @var Request|\Mockery\MockInterface $request */
        $request = mock(Request::class . '[getHostInfo,getUserIP]')->makePartial();
        $request->shouldReceive('getHostInfo')->andReturn('http://localhost');
        $request->shouldReceive('getUserIP')->andReturn($userIP);

        Yii::$app->set('request', $request);
    }

    /**
     * @param string $bearerToken
     */
    private function mockAuthorizationHeader($bearerToken = null) {
        if ($bearerToken !== null) {
            $bearerToken = 'Bearer ' . $bearerToken;
        }

        Yii::$app->request->headers->set('Authorization', $bearerToken);
    }

    private function getComponentConfig() {
        return [
            'identityClass' => Identity::class,
            'enableSession' => false,
            'loginUrl' => null,
            'secret' => 'secret',
            'publicKeyPath' => 'data/certs/public.crt',
            'privateKeyPath' => 'data/certs/private.key',
        ];
    }

}

<?php
namespace codeception\api\unit\components\User;

use api\components\User\AuthenticationResult;
use api\components\User\Component;
use api\components\User\Identity;
use common\models\Account;
use common\models\AccountSession;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\_support\ProtectedCaller;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\AccountSessionFixture;
use tests\codeception\common\fixtures\MinecraftAccessKeyFixture;
use Yii;
use yii\web\Request;

class ComponentTest extends TestCase {
    use ProtectedCaller;

    /**
     * @var Component|\PHPUnit_Framework_MockObject_MockObject
     */
    private $component;

    public function _before() {
        parent::_before();
        $this->component = new Component($this->getComponentConfig());
    }

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'sessions' => AccountSessionFixture::class,
            'minecraftSessions' => MinecraftAccessKeyFixture::class,
        ];
    }

    public function testCreateJwtAuthenticationToken() {
        $this->mockRequest();

        $account = new Account(['id' => 1]);
        $result = $this->component->createJwtAuthenticationToken($account, false);
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertNull($result->getSession());
        $this->assertEquals($account, $result->getAccount());
        $payloads = (new Jwt())->deserialize($result->getJwt())->getPayload();
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals(time(), $payloads->findClaimByName(Claim\IssuedAt::NAME)->getValue(), '', 3);
        /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals(time() + 60 * 60 * 24 * 7, $payloads->findClaimByName('exp')->getValue(), '', 3);
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals('ely|1', $payloads->findClaimByName('sub')->getValue());
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals('accounts_web_user', $payloads->findClaimByName('ely-scopes')->getValue());
        $this->assertNull($payloads->findClaimByName('jti'));

        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $result = $this->component->createJwtAuthenticationToken($account, true);
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertInstanceOf(AccountSession::class, $result->getSession());
        $this->assertEquals($account, $result->getAccount());
        /** @noinspection NullPointerExceptionInspection */
        $this->assertTrue($result->getSession()->refresh());
        $payloads = (new Jwt())->deserialize($result->getJwt())->getPayload();
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals(time(), $payloads->findClaimByName(Claim\IssuedAt::NAME)->getValue(), '', 3);
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals(time() + 3600, $payloads->findClaimByName('exp')->getValue(), '', 3);
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals('ely|1', $payloads->findClaimByName('sub')->getValue());
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals('accounts_web_user', $payloads->findClaimByName('ely-scopes')->getValue());
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals($result->getSession()->id, $payloads->findClaimByName('jti')->getValue());
    }

    public function testRenewJwtAuthenticationToken() {
        $userIP = '192.168.0.1';
        $this->mockRequest($userIP);
        /** @var AccountSession $session */
        $session = $this->tester->grabFixture('sessions', 'admin');
        $result = $this->component->renewJwtAuthenticationToken($session);
        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertEquals($session, $result->getSession());
        $this->assertEquals($session->account_id, $result->getAccount()->id);
        $session->refresh(); // reload data from db
        $this->assertEquals(time(), $session->last_refreshed_at, '', 3);
        $this->assertEquals($userIP, $session->getReadableIp());
        $payloads = (new Jwt())->deserialize($result->getJwt())->getPayload();
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals(time(), $payloads->findClaimByName(Claim\IssuedAt::NAME)->getValue(), '', 3);
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals(time() + 3600, $payloads->findClaimByName('exp')->getValue(), '', 3);
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals('ely|1', $payloads->findClaimByName('sub')->getValue());
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals('accounts_web_user', $payloads->findClaimByName('ely-scopes')->getValue());
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals($session->id, $payloads->findClaimByName('jti')->getValue(), 'session has not changed');
    }

    public function testParseToken() {
        $this->mockRequest();
        $token = $this->callProtected($this->component, 'createToken', new Account(['id' => 1]));
        $jwt = $this->callProtected($this->component, 'serializeToken', $token);
        $this->assertInstanceOf(Token::class, $this->component->parseToken($jwt), 'success get RenewResult object');
    }

    public function testGetActiveSession() {
        $account = $this->tester->grabFixture('accounts', 'admin');
        $result = $this->component->createJwtAuthenticationToken($account, true);
        $this->component->logout();

        /** @var Component|\PHPUnit_Framework_MockObject_MockObject $component */
        $component = $this->getMockBuilder(Component::class)
            ->setMethods(['getIsGuest'])
            ->setConstructorArgs([$this->getComponentConfig()])
            ->getMock();

        $component
            ->expects($this->any())
            ->method('getIsGuest')
            ->willReturn(false);

        $this->mockAuthorizationHeader($result->getJwt());

        $session = $component->getActiveSession();
        $this->assertInstanceOf(AccountSession::class, $session);
        /** @noinspection NullPointerExceptionInspection */
        $this->assertEquals($session->id, $result->getSession()->id);
    }

    public function testTerminateSessions() {
        /** @var AccountSession $session */
        $session = AccountSession::findOne($this->tester->grabFixture('sessions', 'admin2')['id']);

        /** @var Component|\Mockery\MockInterface $component */
        $component = mock(Component::class . '[getActiveSession]', [$this->getComponentConfig()])->shouldDeferMissing();
        $component->shouldReceive('getActiveSession')->times(1)->andReturn($session);

        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $component->createJwtAuthenticationToken($account, true);

        $component->terminateSessions($account, Component::KEEP_MINECRAFT_SESSIONS | Component::KEEP_SITE_SESSIONS);
        $this->assertNotEmpty($account->getMinecraftAccessKeys()->all());
        $this->assertNotEmpty($account->getSessions()->all());

        $component->terminateSessions($account, Component::KEEP_SITE_SESSIONS);
        $this->assertEmpty($account->getMinecraftAccessKeys()->all());
        $this->assertNotEmpty($account->getSessions()->all());

        $component->terminateSessions($account, Component::KEEP_CURRENT_SESSION);
        $sessions = $account->getSessions()->all();
        $this->assertEquals(1, count($sessions));
        $this->assertTrue($sessions[0]->id === $session->id);

        $component->terminateSessions($account);
        $this->assertEmpty($account->getSessions()->all());
        $this->assertEmpty($account->getMinecraftAccessKeys()->all());
    }

    private function mockRequest($userIP = '127.0.0.1') {
        /** @var Request|\Mockery\MockInterface $request */
        $request = mock(Request::class . '[getHostInfo,getUserIP]')->shouldDeferMissing();
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
        ];
    }

}

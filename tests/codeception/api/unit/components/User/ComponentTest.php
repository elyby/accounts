<?php
namespace codeception\api\unit\components\User;

use api\components\User\Component;
use api\components\User\LoginResult;
use api\components\User\RenewResult;
use api\models\AccountIdentity;
use Codeception\Specify;
use common\models\AccountSession;
use Emarref\Jwt\Algorithm\AlgorithmInterface;
use Emarref\Jwt\Claim\ClaimInterface;
use Emarref\Jwt\Claim\Expiration;
use Emarref\Jwt\Token;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\_support\ProtectedCaller;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\AccountSessionFixture;
use Yii;
use yii\web\HeaderCollection;
use yii\web\Request;

class ComponentTest extends TestCase {
    use Specify;
    use ProtectedCaller;

    /**
     * @var Component
     */
    private $component;

    public function _before() {
        parent::_before();
        $this->component = new Component($this->getComponentArguments());
    }

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'sessions' => AccountSessionFixture::class,
        ];
    }

    public function testLogin() {
        $this->mockRequest();
        $this->specify('success get LoginResult object without session value', function() {
            $account = new AccountIdentity(['id' => 1]);
            $result = $this->component->login($account, false);
            expect($result)->isInstanceOf(LoginResult::class);
            expect($result->getSession())->null();
            expect($result->getIdentity())->equals($account);
            $jwt = $result->getJwt();
            expect(is_string($jwt))->true();
            $token = $this->component->parseToken($jwt);
            $claim = $token->getPayload()->findClaimByName(Expiration::NAME);
            // Токен выписывается на 7 дней, но мы проверим хотя бы на 2 суток
            expect($claim->getValue())->greaterThan(time() + 60 * 60 * 24 * 2);
        });

        $this->specify('success get LoginResult object with session value if rememberMe is true', function() {
            /** @var AccountIdentity $account */
            $account = AccountIdentity::findOne($this->tester->grabFixture('accounts', 'admin')['id']);
            $result = $this->component->login($account, true);
            expect($result)->isInstanceOf(LoginResult::class);
            expect($result->getSession())->isInstanceOf(AccountSession::class);
            expect($result->getIdentity())->equals($account);
            expect($result->getSession()->refresh())->true();
            $jwt = $result->getJwt();
            expect(is_string($jwt))->true();
            $token = $this->component->parseToken($jwt);
            $claim = $token->getPayload()->findClaimByName(Expiration::NAME);
            // Токен выписывается на 1 час, т.к. в дальнейшем он будет рефрешиться
            expect($claim->getValue())->lessOrEquals(time() + 3600);
        });
    }

    public function testRenew() {
        $this->specify('success get RenewResult object', function() {
            $userIP = '192.168.0.1';
            $this->mockRequest($userIP);
            /** @var AccountSession $session */
            $session = AccountSession::findOne($this->tester->grabFixture('sessions', 'admin')['id']);
            $callTime = time();
            $result = $this->component->renew($session);
            expect($result)->isInstanceOf(RenewResult::class);
            expect(is_string($result->getJwt()))->true();
            expect($result->getIdentity()->getId())->equals($session->account_id);
            $session->refresh();
            expect($session->last_refreshed_at)->greaterOrEquals($callTime);
            expect($session->getReadableIp())->equals($userIP);
        });
    }

    public function testParseToken() {
        $this->mockRequest();
        $this->specify('success get RenewResult object', function() {
            $identity = new AccountIdentity(['id' => 1]);
            $token = $this->callProtected($this->component, 'createToken', $identity);
            $jwt = $this->callProtected($this->component, 'serializeToken', $token);

            expect($this->component->parseToken($jwt))->isInstanceOf(Token::class);
        });
    }

    public function testGetActiveSession() {
        $this->specify('get used account session', function() {
            /** @var AccountIdentity $identity */
            $identity = AccountIdentity::findOne($this->tester->grabFixture('accounts', 'admin')['id']);
            $result = $this->component->login($identity, true);
            $this->component->logout();

            /** @var Component|\PHPUnit_Framework_MockObject_MockObject $component */
            $component = $this->getMockBuilder(Component::class)
                ->setMethods(['getIsGuest'])
                ->setConstructorArgs([$this->getComponentArguments()])
                ->getMock();

            $component
                ->expects($this->any())
                ->method('getIsGuest')
                ->will($this->returnValue(false));

            /** @var HeaderCollection|\PHPUnit_Framework_MockObject_MockObject $headersCollection */
            $headersCollection = $this->getMockBuilder(HeaderCollection::class)
                ->setMethods(['get'])
                ->getMock();

            $headersCollection
                ->expects($this->any())
                ->method('get')
                ->with($this->equalTo('Authorization'))
                ->will($this->returnValue('Bearer ' . $result->getJwt()));

            /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
            $request = $this->getMockBuilder(Request::class)
                ->setMethods(['getHeaders'])
                ->getMock();

            $request
                ->expects($this->any())
                ->method('getHeaders')
                ->will($this->returnValue($headersCollection));

            Yii::$app->set('request', $request);

            $session = $component->getActiveSession();
            expect($session)->isInstanceOf(AccountSession::class);
            expect($session->id)->equals($result->getSession()->id);
        });
    }

    public function testSerializeToken() {
        $this->specify('get string, contained jwt token', function() {
            $token = new Token();
            expect($this->callProtected($this->component, 'serializeToken', $token))
                ->regExp('/^[A-Za-z0-9-_=]+\.[A-Za-z0-9-_=]+\.?[A-Za-z0-9-_.+\/=]*$/');
        });
    }

    public function testCreateToken() {
        $this->specify('create token', function() {
            expect($this->callProtected($this->component, 'createToken', new AccountIdentity(['id' => 1])))
                ->isInstanceOf(Token::class);
        });
    }

    public function testGetAlgorithm() {
        $this->specify('get expected hash algorithm object', function() {
            expect($this->component->getAlgorithm())->isInstanceOf(AlgorithmInterface::class);
        });
    }

    public function testGetClaims() {
        $this->specify('get expected array of claims', function() {
            $claims = $this->callProtected($this->component, 'getClaims', new AccountIdentity(['id' => 1]));
            expect(is_array($claims))->true();
            expect('all array items should have valid type', array_filter($claims, function($claim) {
                return !$claim instanceof ClaimInterface;
            }))->isEmpty();
        });
    }

    /**
     * @param string $userIP
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockRequest($userIP = '127.0.0.1') {
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['getHostInfo', 'getUserIP'])
            ->getMock();

        $request
            ->expects($this->any())
            ->method('getHostInfo')
            ->will($this->returnValue('http://localhost'));

        $request
            ->expects($this->any())
            ->method('getUserIP')
            ->will($this->returnValue($userIP));

        Yii::$app->set('request', $request);

        return $request;
    }

    private function getComponentArguments() {
        return [
            'identityClass' => AccountIdentity::class,
            'enableSession' => false,
            'loginUrl' => null,
            'secret' => 'secret',
        ];
    }

}

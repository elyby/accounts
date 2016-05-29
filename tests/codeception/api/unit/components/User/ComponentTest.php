<?php
namespace codeception\api\unit\components\User;

use api\components\User\Component;
use api\components\User\LoginResult;
use api\models\AccountIdentity;
use Codeception\Specify;
use common\models\AccountSession;
use Emarref\Jwt\Algorithm\AlgorithmInterface;
use Emarref\Jwt\Claim\ClaimInterface;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\_support\ProtectedCaller;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\AccountSessionFixture;

/**
 * @property AccountFixture $accounts
 * @property AccountSessionFixture $sessions
 */
class ComponentTest extends DbTestCase {
    use Specify;
    use ProtectedCaller;

    private $originalRemoteHost;

    /**
     * @var Component
     */
    private $component;

    public function _before() {
        $this->originalRemoteHost = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        parent::_before();

        $this->component = new Component([
            'identityClass' => AccountIdentity::class,
            'enableSession' => false,
            'loginUrl' => null,
            'secret' => 'secret',
        ]);
    }

    public function _after() {
        parent::_after();
        $_SERVER['REMOTE_ADDR'] = $this->originalRemoteHost;
    }

    public function fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'sessions' => AccountSessionFixture::class,
        ];
    }

    public function testLogin() {
        $this->specify('success get LoginResult object without session value', function() {
            $account = new AccountIdentity(['id' => 1]);
            $result = $this->component->login($account, false);
            expect($result)->isInstanceOf(LoginResult::class);
            expect($result->getSession())->null();
            expect(is_string($result->getJwt()))->true();
            expect($result->getIdentity())->equals($account);
        });

        $this->specify('success get LoginResult object with session value if rememberMe is true', function() {
            /** @var AccountIdentity $account */
            $account = AccountIdentity::findOne($this->accounts['admin']['id']);
            $result = $this->component->login($account, true);
            expect($result)->isInstanceOf(LoginResult::class);
            expect($result->getSession())->isInstanceOf(AccountSession::class);
            expect(is_string($result->getJwt()))->true();
            expect($result->getIdentity())->equals($account);
            expect($result->getSession()->refresh())->true();
        });
    }

    public function testGetJWT() {
        $this->specify('get string, contained jwt token', function() {
            expect($this->component->getJWT(new AccountIdentity(['id' => 1])))
                ->regExp('/^[A-Za-z0-9-_=]+\.[A-Za-z0-9-_=]+\.?[A-Za-z0-9-_.+\/=]*$/');
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

}

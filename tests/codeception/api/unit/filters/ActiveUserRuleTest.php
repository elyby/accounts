<?php
namespace tests\codeception\api\unit\filters;

use api\filters\ActiveUserRule;
use api\models\AccountIdentity;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\_support\ProtectedCaller;
use const common\LATEST_RULES_VERSION;
use yii\base\Action;

class ActiveUserRuleTest extends TestCase {
    use Specify;
    use ProtectedCaller;

    public function testMatchCustom() {
        $account = new AccountIdentity();

        $this->specify('get false if user not finished registration', function() use (&$account) {
            $account->status = Account::STATUS_REGISTERED;
            $filter = $this->getFilterMock($account);
            expect($this->callProtected($filter, 'matchCustom', new Action(null, null)))->false();
        });

        $this->specify('get false if user has banned status', function() use (&$account) {
            $account->status = Account::STATUS_BANNED;
            $filter = $this->getFilterMock($account);
            expect($this->callProtected($filter, 'matchCustom', new Action(null, null)))->false();
        });

        $this->specify('get false if user have old EULA agreement', function() use (&$account) {
            $account->status = Account::STATUS_ACTIVE;
            $account->rules_agreement_version = null;
            $filter = $this->getFilterMock($account);
            expect($this->callProtected($filter, 'matchCustom', new Action(null, null)))->false();
        });

        $this->specify('get true if user fully active', function() use (&$account) {
            $account->status = Account::STATUS_ACTIVE;
            $account->rules_agreement_version = LATEST_RULES_VERSION;
            $filter = $this->getFilterMock($account);
            expect($this->callProtected($filter, 'matchCustom', new Action(null, null)))->true();
        });
    }

    /**
     * @param AccountIdentity $returnIdentity
     * @return ActiveUserRule|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getFilterMock(AccountIdentity $returnIdentity) {
        /** @var ActiveUserRule|\PHPUnit_Framework_MockObject_MockObject $filter */
        $filter = $this
            ->getMockBuilder(ActiveUserRule::class)
            ->setMethods(['getIdentity'])
            ->getMock();

        $filter
            ->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue($returnIdentity));

        return $filter;
    }

}

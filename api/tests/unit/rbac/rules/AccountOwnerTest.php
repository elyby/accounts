<?php
declare(strict_types=1);

namespace api\tests\unit\rbac\rules;

use api\components\User\IdentityInterface;
use api\rbac\rules\AccountOwner;
use common\models\Account;
use common\tests\unit\TestCase;
use InvalidArgumentException;
use Yii;
use yii\rbac\Item;
use const common\LATEST_RULES_VERSION;

class AccountOwnerTest extends TestCase {

    public function testExecute() {
        $rule = new AccountOwner();
        $item = new Item();

        // Identity is null
        $this->assertFalse($rule->execute('some token', $item, ['accountId' => 123]));

        // Identity presented, but have no account
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getAccount')->willReturn(null);
        Yii::$app->user->setIdentity($identity);

        $this->assertFalse($rule->execute('some token', $item, ['accountId' => 123]));

        // Identity has an account
        $account = new Account();
        $account->id = 1;
        $account->status = Account::STATUS_ACTIVE;
        $account->rules_agreement_version = LATEST_RULES_VERSION;

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getAccount')->willReturn($account);

        Yii::$app->user->setIdentity($identity);

        $this->assertFalse($rule->execute('token', $item, ['accountId' => 2]));
        $this->assertFalse($rule->execute('token', $item, ['accountId' => '2']));
        $this->assertTrue($rule->execute('token', $item, ['accountId' => 1]));
        $this->assertTrue($rule->execute('token', $item, ['accountId' => '1']));
        $account->rules_agreement_version = null;
        $this->assertFalse($rule->execute('token', $item, ['accountId' => 1]));
        $this->assertTrue($rule->execute('token', $item, ['accountId' => 1, 'optionalRules' => true]));
        $account->rules_agreement_version = LATEST_RULES_VERSION;
        $account->status = Account::STATUS_BANNED;
        $this->assertFalse($rule->execute('token', $item, ['accountId' => 1]));
        $this->assertFalse($rule->execute('token', $item, ['accountId' => 1, 'optionalRules' => true]));
    }

    public function testExecuteWithoutAccountId() {
        $this->expectException(InvalidArgumentException::class);

        $rule = new AccountOwner();
        $this->assertFalse($rule->execute('token', new Item(), []));
    }

}

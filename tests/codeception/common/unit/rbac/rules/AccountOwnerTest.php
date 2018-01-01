<?php
namespace tests\codeception\common\unit\rbac\rules;

use api\components\User\Component;
use api\components\User\IdentityInterface;
use common\models\Account;
use common\rbac\rules\AccountOwner;
use tests\codeception\common\unit\TestCase;
use Yii;
use yii\rbac\Item;
use const common\LATEST_RULES_VERSION;

class AccountOwnerTest extends TestCase {

    public function testIdentityIsNull() {
        $component = mock(Component::class . '[findIdentityByAccessToken]', [['secret' => 'secret']]);
        $component->shouldDeferMissing();
        $component->shouldReceive('findIdentityByAccessToken')->andReturn(null);

        Yii::$app->set('user', $component);

        $this->assertFalse((new AccountOwner())->execute('some token', new Item(), ['accountId' => 123]));
    }

    public function testExecute() {
        $rule = new AccountOwner();
        $item = new Item();

        $account = new Account();
        $account->id = 1;
        $account->status = Account::STATUS_ACTIVE;
        $account->rules_agreement_version = LATEST_RULES_VERSION;

        $identity = mock(IdentityInterface::class);
        $identity->shouldReceive('getAccount')->andReturn($account);

        $component = mock(Component::class . '[findIdentityByAccessToken]', [['secret' => 'secret']]);
        $component->shouldDeferMissing();
        $component->shouldReceive('findIdentityByAccessToken')->withArgs(['token'])->andReturn($identity);

        Yii::$app->set('user', $component);

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

    /**
     * @expectedException \yii\base\InvalidParamException
     */
    public function testExecuteWithException() {
        (new AccountOwner())->execute('', new Item(), []);
    }

}

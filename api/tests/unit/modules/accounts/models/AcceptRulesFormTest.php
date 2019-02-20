<?php
namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\AcceptRulesForm;
use common\models\Account;
use api\tests\unit\TestCase;
use const common\LATEST_RULES_VERSION;

class AcceptRulesFormTest extends TestCase {

    public function testAgreeWithLatestRules() {
        /** @var Account|\Mockery\MockInterface $account */
        $account = mock(Account::class . '[save]');
        $account->shouldReceive('save')->andReturn(true);
        $account->rules_agreement_version = LATEST_RULES_VERSION - 1;

        $model = new AcceptRulesForm($account);
        $this->assertTrue($model->performAction());
        $this->assertEquals(LATEST_RULES_VERSION, $account->rules_agreement_version);
    }

}

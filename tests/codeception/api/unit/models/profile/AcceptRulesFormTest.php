<?php
namespace codeception\api\unit\models\profile;

use api\models\profile\AcceptRulesForm;
use common\models\Account;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use const common\LATEST_RULES_VERSION;

class AcceptRulesFormTest extends TestCase {

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testAgreeWithLatestRules() {
        /** @var Account $account */
        $account = Account::findOne($this->tester->grabFixture('accounts', 'account-with-old-rules-version'));
        $model = new AcceptRulesForm($account);
        $this->assertTrue($model->agreeWithLatestRules());
        $this->assertEquals(LATEST_RULES_VERSION, $account->rules_agreement_version);
    }

}

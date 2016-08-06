<?php
namespace codeception\api\unit\models\profile;

use api\models\profile\AcceptRulesForm;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use const common\LATEST_RULES_VERSION;

/**
 * @property AccountFixture $accounts
 */
class AcceptRulesFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testApplyLanguage() {
        $this->specify('rules version bumped to latest', function() {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['account-with-old-rules-version']);
            $model = new AcceptRulesForm($account);
            expect($model->agreeWithLatestRules())->true();
            expect($account->rules_agreement_version)->equals(LATEST_RULES_VERSION);
        });
    }

}

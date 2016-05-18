<?php
namespace codeception\api\unit\models\profile;

use api\models\profile\ChangeLanguageForm;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;

/**
 * @property AccountFixture $accounts
 */
class ChangeLanguageFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'accounts' => [
                'class' => AccountFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/accounts.php',
            ],
        ];
    }

    public function testApplyLanguage() {
        $this->specify('language changed', function() {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['admin']);
            $model = new ChangeLanguageForm($account);
            $model->lang = 'ru';
            expect($model->applyLanguage())->true();
            expect($account->lang)->equals('ru');
        });
    }

}

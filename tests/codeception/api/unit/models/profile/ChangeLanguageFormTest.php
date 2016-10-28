<?php
namespace codeception\api\unit\models\profile;

use api\models\profile\ChangeLanguageForm;
use common\models\Account;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;

class ChangeLanguageFormTest extends TestCase {

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class
        ];
    }

    public function testApplyLanguage() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new ChangeLanguageForm($account);
        $model->lang = 'ru';
        $this->assertTrue($model->applyLanguage());
        $this->assertEquals('ru', $account->lang);
    }

}

<?php
namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\ChangeLanguageForm;
use api\tests\unit\TestCase;
use common\models\Account;

class ChangeLanguageFormTest extends TestCase {

    public function testApplyLanguage() {
        /** @var Account|\Mockery\MockInterface $account */
        $account = mock(Account::class . '[save]');
        $account->shouldReceive('save')->andReturn(true);

        $model = new ChangeLanguageForm($account);
        $model->lang = 'ru';
        $this->assertTrue($model->performAction());
        $this->assertSame('ru', $account->lang);
    }

}

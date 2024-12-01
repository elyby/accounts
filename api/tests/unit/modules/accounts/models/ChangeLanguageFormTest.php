<?php
declare(strict_types=1);

namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\ChangeLanguageForm;
use api\tests\unit\TestCase;
use common\models\Account;

class ChangeLanguageFormTest extends TestCase {

    public function testApplyLanguage(): void {
        $account = $this->createPartialMock(Account::class, ['save']);
        $account->method('save')->willReturn(true);

        $model = new ChangeLanguageForm($account);
        $model->lang = 'ru';
        $this->assertTrue($model->performAction());
        $this->assertSame('ru', $account->lang);
    }

}

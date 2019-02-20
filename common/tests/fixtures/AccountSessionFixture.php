<?php
namespace common\tests\fixtures;

use common\models\AccountSession;
use yii\test\ActiveFixture;

class AccountSessionFixture extends ActiveFixture {

    public $modelClass = AccountSession::class;

    public $dataFile = '@root/common/tests/fixtures/data/account-sessions.php';

    public $depends = [
        AccountFixture::class,
    ];

}

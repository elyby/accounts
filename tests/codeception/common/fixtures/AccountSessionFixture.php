<?php
namespace tests\codeception\common\fixtures;

use common\models\AccountSession;
use yii\test\ActiveFixture;

class AccountSessionFixture extends ActiveFixture {

    public $modelClass = AccountSession::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/account-sessions.php';

    public $depends = [
        AccountFixture::class,
    ];

}

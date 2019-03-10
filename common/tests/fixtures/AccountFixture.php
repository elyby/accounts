<?php
namespace common\tests\fixtures;

use common\models\Account;
use yii\test\ActiveFixture;

class AccountFixture extends ActiveFixture {

    public $modelClass = Account::class;

    public $dataFile = '@root/common/tests/fixtures/data/accounts.php';

}

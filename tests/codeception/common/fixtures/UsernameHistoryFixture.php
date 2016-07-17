<?php
namespace tests\codeception\common\fixtures;

use common\models\UsernameHistory;
use yii\test\ActiveFixture;

class UsernameHistoryFixture extends ActiveFixture {

    public $modelClass = UsernameHistory::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/usernames-history.php';

}

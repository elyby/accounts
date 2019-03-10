<?php
namespace common\tests\fixtures;

use common\models\UsernameHistory;
use yii\test\ActiveFixture;

class UsernameHistoryFixture extends ActiveFixture {

    public $modelClass = UsernameHistory::class;

    public $dataFile = '@root/common/tests/fixtures/data/usernames-history.php';

}

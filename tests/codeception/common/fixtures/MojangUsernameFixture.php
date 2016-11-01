<?php
namespace tests\codeception\common\fixtures;

use common\models\MojangUsername;
use yii\test\ActiveFixture;

class MojangUsernameFixture extends ActiveFixture {

    public $modelClass = MojangUsername::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/mojang-usernames.php';

}

<?php
namespace common\tests\fixtures;

use common\models\MojangUsername;
use yii\test\ActiveFixture;

class MojangUsernameFixture extends ActiveFixture {

    public $modelClass = MojangUsername::class;

    public $dataFile = '@root/common/tests/fixtures/data/mojang-usernames.php';

}

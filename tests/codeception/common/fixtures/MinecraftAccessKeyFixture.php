<?php
namespace tests\codeception\common\fixtures;

use common\models\MinecraftAccessKey;
use yii\test\ActiveFixture;

class MinecraftAccessKeyFixture extends ActiveFixture {

    public $modelClass = MinecraftAccessKey::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/minecraft-access-keys.php';

    public $depends = [
        AccountFixture::class,
    ];

}

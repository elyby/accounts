<?php
namespace common\tests\fixtures;

use common\models\MinecraftAccessKey;
use yii\test\ActiveFixture;

class MinecraftAccessKeyFixture extends ActiveFixture {

    public $modelClass = MinecraftAccessKey::class;

    public $dataFile = '@root/common/tests/fixtures/data/minecraft-access-keys.php';

    public $depends = [
        AccountFixture::class,
    ];

}

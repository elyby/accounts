<?php
namespace tests\codeception\common\fixtures;

use common\models\EmailActivation;
use yii\test\ActiveFixture;

class EmailActivationFixture extends ActiveFixture {

    public $modelClass = EmailActivation::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/email-activations.php';

    public $depends = [
        AccountFixture::class,
    ];

}

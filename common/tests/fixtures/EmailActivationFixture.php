<?php
namespace common\tests\fixtures;

use common\models\EmailActivation;
use yii\test\ActiveFixture;

class EmailActivationFixture extends ActiveFixture {

    public $modelClass = EmailActivation::class;

    public $dataFile = '@root/common/tests/fixtures/data/email-activations.php';

    public $depends = [
        AccountFixture::class,
    ];

}

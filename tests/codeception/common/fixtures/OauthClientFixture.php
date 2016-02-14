<?php
namespace tests\codeception\common\fixtures;

use common\models\OauthClient;
use yii\test\ActiveFixture;

class OauthClientFixture extends ActiveFixture {

    public $modelClass = OauthClient::class;

    public $depends = [
        AccountFixture::class,
    ];

}

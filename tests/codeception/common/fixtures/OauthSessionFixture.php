<?php
namespace tests\codeception\common\fixtures;

use common\models\OauthScope;
use common\models\OauthSession;
use yii\test\ActiveFixture;

class OauthSessionFixture extends ActiveFixture {

    public $modelClass = OauthSession::class;

    public $depends = [
        OauthClientFixture::class,
        AccountFixture::class,
    ];

}

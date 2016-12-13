<?php
namespace tests\codeception\common\fixtures;

use common\models\OauthSession;
use yii\test\ActiveFixture;

class OauthSessionFixture extends ActiveFixture {

    public $modelClass = OauthSession::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/oauth-sessions.php';

    public $depends = [
        OauthClientFixture::class,
        AccountFixture::class,
    ];

}

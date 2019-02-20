<?php
namespace common\tests\fixtures;

use common\models\OauthSession;
use yii\test\ActiveFixture;

class OauthSessionFixture extends ActiveFixture {

    public $modelClass = OauthSession::class;

    public $dataFile = '@root/common/tests/fixtures/data/oauth-sessions.php';

    public $depends = [
        OauthClientFixture::class,
        AccountFixture::class,
    ];

}

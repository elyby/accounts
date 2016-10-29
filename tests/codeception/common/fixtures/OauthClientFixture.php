<?php
namespace tests\codeception\common\fixtures;

use common\models\OauthClient;
use yii\test\ActiveFixture;

class OauthClientFixture extends ActiveFixture {

    public $modelClass = OauthClient::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/oauth-clients.php';

    public $depends = [
        AccountFixture::class,
    ];

}

<?php
namespace common\tests\fixtures;

use common\models\OauthClient;
use yii\test\ActiveFixture;

class OauthClientFixture extends ActiveFixture {

    public $modelClass = OauthClient::class;

    public $dataFile = '@root/common/tests/fixtures/data/oauth-clients.php';

    public $depends = [
        AccountFixture::class,
    ];

}

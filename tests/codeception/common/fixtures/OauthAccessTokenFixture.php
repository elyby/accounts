<?php
namespace tests\codeception\common\fixtures;

use common\models\OauthAccessToken;
use yii\test\ActiveFixture;

class OauthAccessTokenFixture extends ActiveFixture  {

    public $modelClass = OauthAccessToken::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/oauth-access-tokens.php';

    public $depends = [
        OauthSessionFixture::class,
    ];

}

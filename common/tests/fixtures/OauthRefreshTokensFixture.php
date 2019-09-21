<?php
declare(strict_types=1);

namespace common\tests\fixtures;

use common\models\OauthRefreshToken;
use yii\test\ActiveFixture;

class OauthRefreshTokensFixture extends ActiveFixture {

    public $modelClass = OauthRefreshToken::class;

    public $dataFile = '@root/common/tests/fixtures/data/oauth-refresh-tokens.php';

    public $depends = [
        OauthSessionFixture::class,
    ];

}

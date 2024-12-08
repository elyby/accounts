<?php
declare(strict_types=1);

namespace common\tests\fixtures;

use common\models\OauthDeviceCode;
use yii\test\ActiveFixture;

final class OauthDeviceCodeFixture extends ActiveFixture {

    public $modelClass = OauthDeviceCode::class;

    public $dataFile = '@root/common/tests/fixtures/data/oauth-device-codes.php';

    public $depends = [
        OauthClientFixture::class,
        AccountFixture::class,
    ];

}

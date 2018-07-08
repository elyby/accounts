<?php
declare(strict_types=1);

namespace tests\codeception\common\fixtures;

use common\models\WebHook;
use yii\test\ActiveFixture;

class WebHooksFixture extends ActiveFixture {

    public $modelClass = WebHook::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/webhooks.php';

}

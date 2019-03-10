<?php
declare(strict_types=1);

namespace common\tests\fixtures;

use common\models\WebHook;
use yii\test\ActiveFixture;

class WebHooksFixture extends ActiveFixture {

    public $modelClass = WebHook::class;

    public $dataFile = '@root/common/tests/fixtures/data/webhooks.php';

}

<?php
declare(strict_types=1);

namespace tests\codeception\common\fixtures;

use common\models\WebHookEvent;
use yii\test\ActiveFixture;

class WebHooksEventsFixture extends ActiveFixture {

    public $modelClass = WebHookEvent::class;

    public $dataFile = '@tests/codeception/common/fixtures/data/webhooks-events.php';

    public $depends = [
        WebHooksFixture::class,
    ];

}

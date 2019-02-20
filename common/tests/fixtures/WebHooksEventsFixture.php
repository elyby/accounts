<?php
declare(strict_types=1);

namespace common\tests\fixtures;

use common\models\WebHookEvent;
use yii\test\ActiveFixture;

class WebHooksEventsFixture extends ActiveFixture {

    public $modelClass = WebHookEvent::class;

    public $dataFile = '@root/common/tests/fixtures/data/webhooks-events.php';

    public $depends = [
        WebHooksFixture::class,
    ];

}

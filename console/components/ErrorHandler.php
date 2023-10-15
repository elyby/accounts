<?php
declare(strict_types=1);

namespace console\components;

use Yii;
use yii\queue\ExecEvent;

final class ErrorHandler {

    public function handleQueueError(ExecEvent $event): void {
        Yii::$app->errorHandler->handleException($event->error);
    }

}

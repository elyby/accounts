<?php
declare(strict_types=1);

namespace console\components;

use Swift_TransportException;
use Yii;
use yii\queue\ExecEvent;

class ErrorHandler {

    public function handleQueueError(ExecEvent $error): void {
        $exception = $error->error;
        if ($exception instanceof Swift_TransportException) {
            Yii::warning($exception);
            return;
        }

        Yii::error($exception);
    }

}

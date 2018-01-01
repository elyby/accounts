<?php
namespace console\components;

use Swift_TransportException;
use Yii;
use yii\queue\ErrorEvent;

class ErrorHandler {

    public function handleQueueError(ErrorEvent $error): void {
        $exception = $error->error;
        if ($exception instanceof Swift_TransportException) {
            Yii::warning($exception);
            return;
        }

        Yii::error($exception);
    }

}

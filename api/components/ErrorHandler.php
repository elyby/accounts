<?php
namespace api\components;

use api\modules\authserver\exceptions\AuthserverException;
use api\modules\session\exceptions\SessionServerException;
use Yii;

class ErrorHandler extends \yii\web\ErrorHandler {

    public function convertExceptionToArray($exception) {
        if ($exception instanceof AuthserverException || $exception instanceof SessionServerException) {
            return [
                'error' => $exception->getName(),
                'errorMessage' => $exception->getMessage(),
            ];
        }

        return parent::convertExceptionToArray($exception);
    }

    public function logException($exception): void {
        if ($exception instanceof AuthserverException) {
            Yii::error($exception, AuthserverException::class . ':' . $exception->getName());
        } elseif ($exception instanceof SessionServerException) {
            Yii::error($exception, SessionServerException::class . ':' . $exception->getName());
        } else {
            parent::logException($exception);
        }
    }

}

<?php
namespace api\components;

use api\modules\authserver\exceptions\AuthserverException;
use api\modules\session\exceptions\SessionServerException;

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

}

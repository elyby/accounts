<?php
namespace api\components;

use api\modules\authserver\exceptions\AuthserverException;

class ErrorHandler extends \yii\web\ErrorHandler {

    public function convertExceptionToArray($exception) {
        if ($exception instanceof AuthserverException) {
            return [
                'error' => $this->getExceptionName($exception),
                'errorMessage' => $exception->getMessage(),
            ];
        }

        return parent::convertExceptionToArray($exception);
    }

}

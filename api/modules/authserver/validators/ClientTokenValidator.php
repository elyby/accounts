<?php
namespace api\modules\authserver\validators;

use api\modules\authserver\exceptions\IllegalArgumentException;

/**
 * Максимальная длина clientToken для нашей базы данных составляет 255.
 * После этого мы не принимаем указанный токен
 */
class ClientTokenValidator extends \yii\validators\RequiredValidator {

    /**
     * @param string $value
     * @return null
     * @throws \api\modules\authserver\exceptions\AuthserverException
     */
    protected function validateValue($value) {
        if (mb_strlen($value) > 255) {
            throw new IllegalArgumentException('clientToken is too long.');
        }

        return null;
    }

}

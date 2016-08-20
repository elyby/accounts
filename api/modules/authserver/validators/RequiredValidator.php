<?php
namespace api\modules\authserver\validators;

use api\modules\authserver\exceptions\IllegalArgumentException;

/**
 * Для данного модуля нам не принципиально, что там за ошибка: если не хватает хотя бы одного
 * параметра - тут же отправляем исключение и дело с концом
 */
class RequiredValidator extends \yii\validators\RequiredValidator {

    /**
     * @param string $value
     * @return null
     * @throws \api\modules\authserver\exceptions\AuthserverException
     */
    protected function validateValue($value) {
        if (parent::validateValue($value) !== null) {
            throw new IllegalArgumentException();
        }

        return null;
    }

}

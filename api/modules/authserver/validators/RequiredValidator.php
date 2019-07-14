<?php
namespace api\modules\authserver\validators;

use api\modules\authserver\exceptions\IllegalArgumentException;

/**
 * For this module, it is not important for us what the error is: if at least one parameter is missing,
 * we immediately throw an exception and that's it.
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

<?php
namespace api\modules\session\validators;

use api\modules\session\exceptions\IllegalArgumentException;

/**
 * For this module, it is not important for us what the error is: if at least one parameter is missing,
 * we immediately throw an exception and that's it.
 */
class RequiredValidator extends \yii\validators\RequiredValidator {

    /**
     * @param string $value
     * @throws \api\modules\session\exceptions\SessionServerException
     */
    protected function validateValue($value): ?array {
        if (parent::validateValue($value) !== null) {
            throw new IllegalArgumentException();
        }

        return null;
    }

}

<?php
namespace api\modules\authserver\validators;

use api\modules\authserver\exceptions\IllegalArgumentException;

/**
 * The maximum length of clientToken for our database is 255.
 * If the token is longer, we do not accept the passed token at all.
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

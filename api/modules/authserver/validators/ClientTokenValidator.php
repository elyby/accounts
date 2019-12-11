<?php
declare(strict_types=1);

namespace api\modules\authserver\validators;

use api\modules\authserver\exceptions\IllegalArgumentException;
use yii\validators\Validator;

/**
 * The maximum length of clientToken for our database is 255.
 * If the token is longer, we do not accept the passed token at all.
 */
class ClientTokenValidator extends Validator {

    /**
     * @param string $value
     * @return null
     * @throws \api\modules\authserver\exceptions\AuthserverException
     */
    protected function validateValue($value): ?array {
        if (mb_strlen($value) > 255) {
            throw new IllegalArgumentException('clientToken is too long.');
        }

        return null;
    }

}

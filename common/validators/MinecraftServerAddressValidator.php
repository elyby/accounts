<?php
declare(strict_types=1);

namespace common\validators;

use yii\validators\Validator;

class MinecraftServerAddressValidator extends Validator {

    protected function validateValue($value): ?array {
        // we will add minecraft protocol to help parse_url understand all another parts
        $urlParts = parse_url('minecraft://' . $value);
        $cnt = count($urlParts);
        // scheme will be always presented, so we need to increase expected $cnt by 1
        if (($cnt === 3 && isset($urlParts['host'], $urlParts['port']))
         || ($cnt === 2 && isset($urlParts['host']))
        ) {
            return null;
        }

        return [$this->message, []];
    }

}

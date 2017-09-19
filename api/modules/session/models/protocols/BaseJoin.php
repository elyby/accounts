<?php
namespace api\modules\session\models\protocols;

abstract class BaseJoin implements JoinInterface {

    protected function isEmpty($value): bool {
        return $value === null || $value === '';
    }

}

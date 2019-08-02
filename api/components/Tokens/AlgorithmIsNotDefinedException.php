<?php
declare(strict_types=1);

namespace api\components\Tokens;

class AlgorithmIsNotDefinedException extends \Exception {

    public function __construct(string $algorithmId) {
        parent::__construct("Algorithm with id \"{$algorithmId}\" is not defined");
    }

}

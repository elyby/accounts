<?php
declare(strict_types=1);

namespace api\components\Tokens;

final readonly class EncryptedValue {

    public function __construct(public string $value) {
    }

}

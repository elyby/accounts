<?php
declare(strict_types=1);

namespace api\components\Tokens;

class EncryptedValue {

    /**
     * @var string
     */
    private $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    public function getValue(): string {
        return $this->value;
    }

}

<?php
namespace api\components\OAuth2\Utils\KeyAlgorithm;

use League\OAuth2\Server\Util\KeyAlgorithm\KeyAlgorithmInterface;
use Ramsey\Uuid\Uuid;

class UuidAlgorithm implements KeyAlgorithmInterface {

    /**
     * @inheritdoc
     */
    public function generate($len = 40) : string {
        return Uuid::uuid4()->toString();
    }

}

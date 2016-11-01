<?php
namespace common\components\oauth\Util\KeyAlgorithm;

use League\OAuth2\Server\Util\KeyAlgorithm\DefaultAlgorithm;
use Ramsey\Uuid\Uuid;

class UuidAlgorithm extends DefaultAlgorithm {

    /**
     * @inheritdoc
     */
    public function generate($len = 40) : string {
        return Uuid::uuid4()->toString();
    }

}

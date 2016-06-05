<?php
namespace api\components\User;

use Emarref\Jwt\Claim\AbstractClaim;

class SessionIdClaim extends AbstractClaim {

    const NAME = 'sid';

    /**
     * @inheritdoc
     */
    public function getName() {
        return self::NAME;
    }

}

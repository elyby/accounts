<?php
declare(strict_types=1);

namespace common\components\Authentication\Entities;

use common\models\Account;
use common\models\AccountSession;

final readonly class AuthenticationResult {

    public function __construct(
        public Account $account,
        public ?AccountSession $session = null,
    ) {
    }

}

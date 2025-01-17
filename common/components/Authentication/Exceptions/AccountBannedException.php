<?php
declare(strict_types=1);

namespace common\components\Authentication\Exceptions;

use common\models\Account;
use Exception;
use Throwable;

final class AccountBannedException extends Exception implements AuthenticationException {

    public function __construct(
        public readonly Account $account,
        ?Throwable $previous = null,
    ) {
        parent::__construct('The account has been banned', previous: $previous);
    }

}

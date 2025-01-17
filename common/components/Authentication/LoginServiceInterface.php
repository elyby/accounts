<?php
declare(strict_types=1);

namespace common\components\Authentication;

use common\components\Authentication\Entities\AuthenticationResult;
use common\components\Authentication\Entities\Credentials;
use common\models\AccountSession;

interface LoginServiceInterface {

    /**
     * @throws \common\components\Authentication\Exceptions\AuthenticationException
     */
    public function loginByCredentials(Credentials $credentials): AuthenticationResult;

    public function logout(AccountSession $session): void;

}

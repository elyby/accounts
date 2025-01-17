<?php
declare(strict_types=1);

namespace common\components\Authentication;

use api\validators\TotpValidator;
use common\components\Authentication\Entities\AuthenticationResult;
use common\components\Authentication\Entities\Credentials;
use common\models\Account;
use common\models\AccountSession;
use Webmozart\Assert\Assert;
use Yii;

final class LoginService implements LoginServiceInterface {

    public function loginByCredentials(Credentials $credentials): AuthenticationResult {
        /** @var Account|null $account */
        $account = Account::find()->andWhereLogin($credentials->login)->one();
        if ($account === null) {
            throw new Exceptions\UnknownLoginException();
        }

        if (!$account->validatePassword($credentials->password)) {
            throw new Exceptions\InvalidPasswordException();
        }

        if ($account->is_otp_enabled) {
            if (empty($credentials->totp)) {
                throw new Exceptions\TotpRequiredException();
            }

            $validator = new TotpValidator(['account' => $account]);
            if (!$validator->validate($credentials->totp)) {
                throw new Exceptions\InvalidTotpException();
            }
        }

        if ($account->status === Account::STATUS_BANNED) {
            throw new Exceptions\AccountBannedException($account);
        }

        if ($account->status === Account::STATUS_REGISTERED) {
            throw new Exceptions\AccountNotActivatedException($account);
        }

        if ($account->password_hash_strategy !== Account::PASS_HASH_STRATEGY_YII2) {
            $account->setPassword($credentials->password);
            Assert::true($account->save(), 'Unable to upgrade user\'s password');
        }

        $session = null;
        if ($credentials->rememberMe) {
            $session = new AccountSession();
            $session->account_id = $account->id;
            $session->setIp(Yii::$app->request->userIP);
            $session->generateRefreshToken();
            Assert::true($session->save(), 'Cannot save account session model');
        }

        return new AuthenticationResult($account, $session);
    }

    public function logout(AccountSession $session): void {
        $session->delete();
    }

}

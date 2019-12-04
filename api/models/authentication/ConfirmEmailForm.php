<?php
declare(strict_types=1);

namespace api\models\authentication;

use api\aop\annotations\CollectModelMetrics;
use api\models\base\ApiForm;
use api\validators\EmailActivationKeyValidator;
use common\models\Account;
use common\models\AccountSession;
use common\models\EmailActivation;
use Webmozart\Assert\Assert;
use Yii;

class ConfirmEmailForm extends ApiForm {

    public $key;

    public function rules(): array {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION],
        ];
    }

    /**
     * @CollectModelMetrics(prefix="signup.confirmEmail")
     */
    public function confirm(): ?AuthenticationResult {
        if (!$this->validate()) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /** @var \common\models\confirmations\RegistrationConfirmation $confirmModel */
        $confirmModel = $this->key;
        $account = $confirmModel->account;
        $account->status = Account::STATUS_ACTIVE;
        /** @noinspection PhpUnhandledExceptionInspection */
        Assert::notSame($confirmModel->delete(), false, 'Unable remove activation key.');

        Assert::true($account->save(), 'Unable activate user account.');

        $session = new AccountSession();
        $session->account_id = $account->id;
        $session->setIp(Yii::$app->request->userIP);
        $session->generateRefreshToken();
        Assert::true($session->save(), 'Cannot save account session model');

        $token = Yii::$app->tokensFactory->createForWebAccount($account, $session);

        $transaction->commit();

        return new AuthenticationResult($token, $session->refresh_token);
    }

}

<?php
namespace api\modules\accounts\models;

use api\models\base\BaseAccountForm;
use api\rbac\Permissions as P;
use common\models\Account;
use yii\di\Instance;
use yii\web\User;

class AccountInfo extends BaseAccountForm {

    /**
     * @var User|string
     */
    public $user = 'user';

    public function init() {
        parent::init();
        $this->user = Instance::ensure($this->user, User::class);
    }

    public function info(): array {
        $account = $this->getAccount();

        $response = [
            'id' => $account->id,
            'uuid' => $account->uuid,
            'username' => $account->username,
            'isOtpEnabled' => (bool)$account->is_otp_enabled,
            'registeredAt' => $account->created_at,
            'lang' => $account->lang,
            'elyProfileLink' => $account->getProfileLink(),
        ];

        $authManagerParams = [
            'accountId' => $account->id,
            'optionalRules' => true,
        ];

        if ($this->user->can(P::OBTAIN_ACCOUNT_EMAIL, $authManagerParams)) {
            $response['email'] = $account->email;
        }

        if ($this->user->can(P::OBTAIN_EXTENDED_ACCOUNT_INFO, $authManagerParams)) {
            $response['isActive'] = $account->status === Account::STATUS_ACTIVE;
            $response['passwordChangedAt'] = $account->password_changed_at;
            $response['hasMojangUsernameCollision'] = $account->hasMojangUsernameCollision();
            $response['shouldAcceptRules'] = !$account->isAgreedWithActualRules();
        }

        return $response;
    }

}

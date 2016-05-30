<?php
namespace api\models\authentication;

use api\models\base\ApiForm;
use common\models\AccountSession;
use Yii;

class RefreshTokenForm extends ApiForm {

    public $refresh_token;

    /**
     * @var AccountSession|null
     */
    private $session;

    public function rules() {
        return [
            ['refresh_token', 'required'],
            ['refresh_token', 'validateRefreshToken'],
        ];
    }

    public function validateRefreshToken() {
        if (!$this->hasErrors()) {
            /** @var AccountSession|null $token */
            if ($this->getSession() === null) {
                $this->addError('refresh_token', 'error.refresh_token_not_exist');
            }
        }
    }

    /**
     * @return \api\components\User\RenewResult|bool
     */
    public function renew() {
        if (!$this->validate()) {
            return false;
        }

        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;

        return $component->renew($this->getSession());
    }

    /**
     * @return AccountSession|null
     */
    public function getSession() {
        if ($this->session === null) {
            $this->session = AccountSession::findOne(['refresh_token' => $this->refresh_token]);
        }

        return $this->session;
    }

}

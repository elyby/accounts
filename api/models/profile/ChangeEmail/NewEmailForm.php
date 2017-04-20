<?php
namespace api\models\profile\ChangeEmail;

use api\emails\EmailHelper;
use api\models\base\ApiForm;
use api\validators\EmailActivationKeyValidator;
use common\models\Account;
use common\models\confirmations\NewEmailConfirmation;
use common\models\EmailActivation;
use common\validators\EmailValidator;
use Yii;
use yii\base\ErrorException;

class NewEmailForm extends ApiForm {

    public $key;

    public $email;

    /**
     * @var Account
     */
    private $account;

    public function rules() {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION],
            ['email', EmailValidator::class],
        ];
    }

    public function getAccount(): Account {
        return $this->account;
    }

    public function sendNewEmailConfirmation(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /** @var \common\models\confirmations\CurrentEmailConfirmation $previousActivation */
        $previousActivation = $this->key;
        $previousActivation->delete();

        $activation = $this->createCode();

        EmailHelper::changeEmailConfirmNew($activation);

        $transaction->commit();

        return true;
    }

    /**
     * @return NewEmailConfirmation
     * @throws ErrorException
     */
    public function createCode() {
        $emailActivation = new NewEmailConfirmation();
        $emailActivation->account_id = $this->getAccount()->id;
        $emailActivation->newEmail = $this->email;
        if (!$emailActivation->save()) {
            throw new ErrorException('Cannot save email activation model');
        }

        return $emailActivation;
    }

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

}

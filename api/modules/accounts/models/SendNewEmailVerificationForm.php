<?php
namespace api\modules\accounts\models;

use api\aop\annotations\CollectModelMetrics;
use api\exceptions\ThisShouldNotHappenException;
use common\emails\EmailHelper;
use api\validators\EmailActivationKeyValidator;
use common\models\confirmations\NewEmailConfirmation;
use common\models\EmailActivation;
use common\validators\EmailValidator;
use Yii;

class SendNewEmailVerificationForm extends AccountActionForm {

    public $key;

    public $email;

    public function rules(): array {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION],
            ['email', EmailValidator::class],
        ];
    }

    /**
     * @CollectModelMetrics(prefix="accounts.sendNewEmailVerification")
     */
    public function performAction(): bool {
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

    public function createCode(): NewEmailConfirmation {
        $emailActivation = new NewEmailConfirmation();
        $emailActivation->account_id = $this->getAccount()->id;
        $emailActivation->newEmail = $this->email;
        if (!$emailActivation->save()) {
            throw new ThisShouldNotHappenException('Cannot save email activation model');
        }

        return $emailActivation;
    }

}

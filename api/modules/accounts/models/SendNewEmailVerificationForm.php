<?php
namespace api\modules\accounts\models;

use api\aop\annotations\CollectModelMetrics;
use api\validators\EmailActivationKeyValidator;
use common\models\confirmations\NewEmailConfirmation;
use common\models\EmailActivation;
use common\tasks\SendNewEmailConfirmation;
use common\validators\EmailValidator;
use Webmozart\Assert\Assert;
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

        Yii::$app->queue->push(SendNewEmailConfirmation::createFromConfirmation($activation));

        $transaction->commit();

        return true;
    }

    public function createCode(): NewEmailConfirmation {
        $emailActivation = new NewEmailConfirmation();
        $emailActivation->account_id = $this->getAccount()->id;
        $emailActivation->newEmail = $this->email;
        Assert::true($emailActivation->save(), 'Cannot save email activation model');

        return $emailActivation;
    }

}

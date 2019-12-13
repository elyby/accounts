<?php
declare(strict_types=1);

namespace api\modules\accounts\models;

use api\aop\annotations\CollectModelMetrics;
use api\validators\PasswordRequiredValidator;
use common\helpers\Error as E;
use common\models\confirmations\CurrentEmailConfirmation;
use common\models\EmailActivation;
use common\tasks\SendCurrentEmailConfirmation;
use Webmozart\Assert\Assert;
use Yii;

class SendEmailVerificationForm extends AccountActionForm {

    public $password;

    /**
     * @var null meta-field to force yii to validate and publish errors related to sent emails
     */
    public $email;

    public function rules(): array {
        return [
            ['email', 'validateFrequency', 'skipOnEmpty' => false],
            ['password', PasswordRequiredValidator::class, 'account' => $this->getAccount()],
        ];
    }

    public function validateFrequency(string $attribute): void {
        if (!$this->hasErrors()) {
            $emailConfirmation = $this->getEmailActivation();
            if ($emailConfirmation !== null && !$emailConfirmation->canRepeat()) {
                $this->addError($attribute, E::RECENTLY_SENT_MESSAGE);
            }
        }
    }

    /**
     * @CollectModelMetrics(prefix="accounts.sendEmailVerification")
     */
    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $this->removeOldCode();
        $activation = $this->createCode();

        Yii::$app->queue->push(SendCurrentEmailConfirmation::createFromConfirmation($activation));

        $transaction->commit();

        return true;
    }

    public function createCode(): CurrentEmailConfirmation {
        $account = $this->getAccount();
        $emailActivation = new CurrentEmailConfirmation();
        $emailActivation->account_id = $account->id;
        Assert::true($emailActivation->save(), 'Cannot save email activation model');

        return $emailActivation;
    }

    public function removeOldCode(): void {
        $emailActivation = $this->getEmailActivation();
        if ($emailActivation === null) {
            return;
        }

        $emailActivation->delete();
    }

    /**
     * Returns the E-mail activation that was used within the process to move on to the next step.
     * The method is designed to check if the E-mail change messages are sent too often.
     * Including checking for the confirmation of the new E-mail type, because when you go to this step,
     * the activation of the previous step is removed.
     */
    public function getEmailActivation(): ?EmailActivation {
        return $this->getAccount()
            ->getEmailActivations()
            ->withType(
                EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION,
                EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION
            )
            ->one();
    }

}

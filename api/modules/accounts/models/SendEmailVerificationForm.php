<?php
namespace api\modules\accounts\models;

use api\exceptions\ThisShouldNotHappenException;
use common\emails\EmailHelper;
use api\validators\PasswordRequiredValidator;
use common\helpers\Error as E;
use common\models\confirmations\CurrentEmailConfirmation;
use common\models\EmailActivation;
use Yii;

class SendEmailVerificationForm extends AccountActionForm {

    public $password;

    /**
     * @var null meta-поле, чтобы заставить yii валидировать и публиковать ошибки, связанные с отправленными email
     */
    public $email;

    public function rules(): array {
        return [
            ['email', 'validateFrequency', 'skipOnEmpty' => false],
            ['password', PasswordRequiredValidator::class, 'account' => $this->getAccount()],
        ];
    }

    public function validateFrequency($attribute): void {
        if (!$this->hasErrors()) {
            $emailConfirmation = $this->getEmailActivation();
            if ($emailConfirmation !== null && !$emailConfirmation->canRepeat()) {
                $this->addError($attribute, E::RECENTLY_SENT_MESSAGE);
            }
        }
    }

    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $this->removeOldCode();
        $activation = $this->createCode();

        EmailHelper::changeEmailConfirmCurrent($activation);

        $transaction->commit();

        return true;
    }

    public function createCode(): CurrentEmailConfirmation {
        $account = $this->getAccount();
        $emailActivation = new CurrentEmailConfirmation();
        $emailActivation->account_id = $account->id;
        if (!$emailActivation->save()) {
            throw new ThisShouldNotHappenException('Cannot save email activation model');
        }

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
     * Возвращает E-mail активацию, которая использовалась внутри процесса для перехода на следующий шаг.
     * Метод предназначен для проверки, не слишком ли часто отправляются письма о смене E-mail.
     * Проверяем тип подтверждения нового E-mail, поскольку при переходе на этот этап, активация предыдущего
     * шага удаляется.
     */
    public function getEmailActivation(): ?EmailActivation {
        return $this->getAccount()
            ->getEmailActivations()
            ->andWhere([
                'type' => [
                    EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION,
                    EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
                ],
            ])
            ->one();
    }

}

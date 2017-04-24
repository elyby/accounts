<?php
namespace api\models\profile\ChangeEmail;

use common\emails\EmailHelper;
use api\models\base\ApiForm;
use api\validators\PasswordRequiredValidator;
use common\helpers\Error as E;
use common\models\Account;
use common\models\confirmations\CurrentEmailConfirmation;
use common\models\EmailActivation;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;

class InitStateForm extends ApiForm {

    public $email;

    public $password;

    private $account;

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        $this->email = $account->email;
        parent::__construct($config);
    }

    public function getAccount() : Account {
        return $this->account;
    }

    public function rules() {
        return [
            ['email', 'validateFrequency'],
            ['password', PasswordRequiredValidator::class, 'account' => $this->account],
        ];
    }

    public function validateFrequency($attribute) {
        if (!$this->hasErrors()) {
            $emailConfirmation = $this->getEmailActivation();
            if ($emailConfirmation !== null && !$emailConfirmation->canRepeat()) {
                $this->addError($attribute, E::RECENTLY_SENT_MESSAGE);
            }
        }
    }

    public function sendCurrentEmailConfirmation() : bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->removeOldCode();
            $activation = $this->createCode();

            EmailHelper::changeEmailConfirmCurrent($activation);

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * @return CurrentEmailConfirmation
     * @throws ErrorException
     */
    public function createCode() : CurrentEmailConfirmation {
        $account = $this->getAccount();
        $emailActivation = new CurrentEmailConfirmation();
        $emailActivation->account_id = $account->id;
        if (!$emailActivation->save()) {
            throw new ErrorException('Cannot save email activation model');
        }

        return $emailActivation;
    }

    /**
     * Удаляет старый ключ активации, если он существует
     */
    public function removeOldCode() {
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
     * @return EmailActivation|null
     * @throws ErrorException
     */
    public function getEmailActivation() {
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

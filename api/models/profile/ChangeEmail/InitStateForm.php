<?php
namespace api\models\profile\ChangeEmail;

use common\models\Account;
use common\models\confirmations\CurrentEmailConfirmation;
use common\models\EmailActivation;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class InitStateForm extends \api\models\base\PasswordProtectedForm {

    public $email;

    private $account;

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

    public function getAccount() {
        return $this->account;
    }

    public function rules() {
        // TODO: поверить наличие уже отправленных подтверждений смены E-mail
        return array_merge(parent::rules(), [
            ['!email', 'validateAccountPasswordHashStrategy', 'skipOnEmpty' => false],
        ]);
    }

    public function validateAccountPasswordHashStrategy($attribute) {
        $account = $this->getAccount();
        if ($account->password_hash_strategy === Account::PASS_HASH_STRATEGY_OLD_ELY) {
            $this->addError($attribute, 'error.old_hash_strategy');
        }
    }

    public function sendCurrentEmailConfirmation() {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $activation = $this->createCode();
            $this->sendCode($activation);

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
    public function createCode() {
        $account = $this->getAccount();
        $emailActivation = new CurrentEmailConfirmation();
        $emailActivation->account_id = $account->id;
        if (!$emailActivation->save()) {
            throw new ErrorException('Cannot save email activation model');
        }

        return $emailActivation;
    }

    public function sendCode(EmailActivation $code) {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        $fromEmail = Yii::$app->params['fromEmail'];
        if (!$fromEmail) {
            throw new InvalidConfigException('Please specify fromEmail app in app params');
        }

        $acceptor = $code->account;
        /** @var \yii\swiftmailer\Message $message */
        $message = $mailer->compose([
            'html' => '@app/mails/current-email-confirmation-html',
            'text' => '@app/mails/current-email-confirmation-text',
        ], [
            'key' => $code->key,
        ])
            ->setTo([$acceptor->email => $acceptor->username])
            ->setFrom([$fromEmail => 'Ely.by Accounts'])
            ->setSubject('Ely.by Account change E-mail confirmation');

        if (!$message->send()) {
            throw new ErrorException('Unable send email with activation code.');
        }
    }

}

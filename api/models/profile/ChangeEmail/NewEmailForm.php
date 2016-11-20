<?php
namespace api\models\profile\ChangeEmail;

use api\models\base\KeyConfirmationForm;
use common\models\Account;
use common\models\confirmations\NewEmailConfirmation;
use common\models\EmailActivation;
use common\validators\EmailValidator;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class NewEmailForm extends KeyConfirmationForm {

    public $email;

    /**
     * @var Account
     */
    private $account;

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

    public function rules() {
        return array_merge(parent::rules(), [
            ['email', EmailValidator::class],
        ]);
    }

    public function getAccount() : Account {
        return $this->account;
    }

    public function sendNewEmailConfirmation() {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $previousActivation = $this->getActivationCodeModel();
            $previousActivation->delete();

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
            'html' => '@app/mails/new-email-confirmation-html',
            'text' => '@app/mails/new-email-confirmation-text',
        ], [
            'key' => $code->key,
            'account' => $acceptor,
        ])
            ->setTo([$this->email => $acceptor->username])
            ->setFrom([$fromEmail => 'Ely.by Accounts'])
            ->setSubject('Ely.by Account new E-mail confirmation');

        if (!$message->send()) {
            throw new ErrorException('Unable send email with activation code.');
        }
    }

}

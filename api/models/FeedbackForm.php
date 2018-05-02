<?php
namespace api\models;

use api\models\base\ApiForm;
use common\helpers\Error as E;
use common\models\Account;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;

class FeedbackForm extends ApiForm {

    public $subject;

    public $email;

    public $type;

    public $message;

    public function rules() {
        return [
            ['subject', 'required', 'message' => E::SUBJECT_REQUIRED],
            ['email', 'required', 'message' => E::EMAIL_REQUIRED],
            ['message', 'required', 'message' => E::MESSAGE_REQUIRED],
            [['subject'], 'string', 'max' => 255],
            [['email'], 'email', 'message' => E::EMAIL_INVALID],
            [['message'], 'string', 'max' => 65535],
        ];
    }

    public function sendMessage(): bool {
        if (!$this->validate()) {
            return false;
        }

        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        $supportEmail = Yii::$app->params['supportEmail'];
        if (!$supportEmail) {
            throw new InvalidConfigException('Please specify supportEmail value in app params');
        }

        $account = $this->getAccount();
        /** @var \yii\swiftmailer\Message $message */
        $message = $mailer->compose('@common/emails/views/feedback', [
            'model' => $this,
            'account' => $account,
        ]);
        $message
            ->setTo($supportEmail)
            ->setFrom([$this->email => $account ? $account->username : $this->email])
            ->setSubject($this->subject);

        if (!$message->send()) {
            throw new ErrorException('Unable send feedback email.');
        }

        return true;
    }

    protected function getAccount(): ?Account {
        $identity = Yii::$app->user->identity;
        if ($identity === null) {
            return null;
        }

        return $identity->getAccount();
    }

}

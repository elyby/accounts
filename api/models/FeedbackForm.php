<?php
declare(strict_types=1);

namespace api\models;

use api\models\base\ApiForm;
use common\helpers\Error as E;
use common\models\Account;
use Webmozart\Assert\Assert;
use Yii;
use yii\base\InvalidConfigException;

class FeedbackForm extends ApiForm {

    public mixed $subject = null;

    public mixed $email = null;

    public mixed $type = null;

    public mixed $message = null;

    public function rules(): array {
        return [
            [['subject'], 'required', 'message' => E::SUBJECT_REQUIRED],
            [['email'], 'required', 'message' => E::EMAIL_REQUIRED],
            [['message'], 'required', 'message' => E::MESSAGE_REQUIRED],
            [['subject'], 'string', 'max' => 255],
            [['email'], 'email', 'message' => E::EMAIL_INVALID],
            [['message'], 'string', 'max' => 65535],
        ];
    }

    /**
     * @throws InvalidConfigException
     */
    public function sendMessage(): bool {
        if (!$this->validate()) {
            return false;
        }

        /** @var \yii\symfonymailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        $supportEmail = Yii::$app->params['supportEmail'];
        if (!$supportEmail) {
            throw new InvalidConfigException('Please specify supportEmail value in the app params');
        }

        $account = $this->getAccount();
        /** @var \yii\symfonymailer\Message $message */
        $message = $mailer->compose('@common/emails/views/feedback', [
            'model' => $this,
            'account' => $account,
        ]);
        $message
            ->setTo($supportEmail)
            ->setFrom([$this->email => $account?->username ?? $this->email])
            ->setSubject($this->subject);

        Assert::true($message->send(), 'Unable send feedback email.');

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

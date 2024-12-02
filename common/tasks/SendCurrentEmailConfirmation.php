<?php
declare(strict_types=1);

namespace common\tasks;

use common\emails\EmailHelper;
use common\emails\templates\ChangeEmail;
use common\models\confirmations\CurrentEmailConfirmation;
use Yii;
use yii\mail\MailerInterface;
use yii\queue\RetryableJobInterface;

class SendCurrentEmailConfirmation implements RetryableJobInterface {

    public mixed $email = null;

    public mixed $username = null;

    public mixed $code = null;

    public function __construct(public MailerInterface $mailer) {
    }

    public static function createFromConfirmation(CurrentEmailConfirmation $confirmation): self {
        $result = new self(Yii::$app->mailer);
        $result->email = $confirmation->account->email;
        $result->username = $confirmation->account->username;
        $result->code = $confirmation->key;

        return $result;
    }

    public function getTtr(): int {
        return 30;
    }

    public function canRetry($attempt, $error): bool {
        return true;
    }

    /**
     * @param \yii\queue\Queue $queue
     * @throws \common\emails\exceptions\CannotSendEmailException
     */
    public function execute($queue): void {
        Yii::$app->statsd->inc('queue.sendCurrentEmailConfirmation.attempt');
        $template = new ChangeEmail($this->mailer);
        $template->setKey($this->code);
        $template->send(EmailHelper::buildTo($this->username, $this->email));
    }

}

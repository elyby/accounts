<?php
declare(strict_types=1);

namespace common\tasks;

use common\emails\EmailHelper;
use common\emails\templates\ChangeEmail;
use common\models\confirmations\CurrentEmailConfirmation;
use Yii;
use yii\queue\RetryableJobInterface;

class SendCurrentEmailConfirmation implements RetryableJobInterface {

    public $email;

    public $username;

    public $code;

    public static function createFromConfirmation(CurrentEmailConfirmation $confirmation): self {
        $result = new self();
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
    public function execute($queue) {
        Yii::$app->statsd->inc('queue.sendCurrentEmailConfirmation.attempt');
        $template = new ChangeEmail(Yii::$app->mailer);
        $template->setKey($this->code);
        $template->send(EmailHelper::buildTo($this->username, $this->email));
    }

}

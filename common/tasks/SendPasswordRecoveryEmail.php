<?php
declare(strict_types=1);

namespace common\tasks;

use common\emails\EmailHelper;
use common\emails\templates\ForgotPasswordEmail;
use common\emails\templates\ForgotPasswordParams;
use common\models\confirmations\ForgotPassword;
use Yii;
use yii\queue\RetryableJobInterface;

class SendPasswordRecoveryEmail implements RetryableJobInterface {

    public $username;

    public $email;

    public $code;

    public $link;

    public $locale;

    public static function createFromConfirmation(ForgotPassword $confirmation): self {
        $account = $confirmation->account;

        $result = new self();
        $result->username = $account->username;
        $result->email = $account->email;
        $result->code = $confirmation->key;
        $result->link = Yii::$app->request->getHostInfo() . '/recover-password/' . $confirmation->key;
        $result->locale = $account->lang;

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
        Yii::$app->statsd->inc('queue.sendPasswordRecovery.attempt');
        $template = new ForgotPasswordEmail(Yii::$app->mailer, Yii::$app->emailsRenderer);
        $template->setLocale($this->locale);
        $template->setParams(new ForgotPasswordParams($this->username, $this->code, $this->link));
        $template->send(EmailHelper::buildTo($this->username, $this->email));
    }

}

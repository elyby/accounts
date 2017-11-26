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

    public function getTtr() {
        return 30;
    }

    public function canRetry($attempt, $error) {
        return true;
    }

    /**
     * @param \yii\queue\Queue $queue
     * @throws \common\emails\exceptions\CannotSendEmailException
     */
    public function execute($queue) {
        $params = new ForgotPasswordParams($this->username, $this->code, $this->link);
        $to = EmailHelper::buildTo($this->username, $this->email);
        $template = new ForgotPasswordEmail($to, $this->locale, $params);
        $template->send();
    }

}

<?php
declare(strict_types=1);
namespace common\tasks;

use common\emails\EmailHelper;
use common\emails\templates\ChangeEmailConfirmCurrentEmail;
use common\models\confirmations\CurrentEmailConfirmation;
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

    public function getTtr() {
        return 30;
    }

    public function canRetry($attempt, $error) {
        return true;
    }

    /**
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue) {
        $to = EmailHelper::buildTo($this->username, $this->email);
        $template = new ChangeEmailConfirmCurrentEmail($to, $this->code);
        $template->send();
    }

}

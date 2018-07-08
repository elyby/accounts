<?php
declare(strict_types=1);

namespace common\tasks;

use common\models\Account;
use common\models\WebHook;
use Yii;
use yii\queue\RetryableJobInterface;

class CreateWebHooksDeliveries implements RetryableJobInterface {

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $payloads;

    public static function createAccountEdit(Account $account, array $changedAttributes): self {
        $result = new static();
        $result->type = 'account.edit';
        $result->payloads = [
            'id' => $account->id,
            'uuid' => $account->uuid,
            'username' => $account->username,
            'email' => $account->email,
            'lang' => $account->lang,
            'isActive' => $account->status === Account::STATUS_ACTIVE,
            'registered' => date('c', (int)$account->created_at),
            'changedAttributes' => $changedAttributes,
        ];

        return $result;
    }

    /**
     * @return int time to reserve in seconds
     */
    public function getTtr() {
        return 10;
    }

    /**
     * @param int $attempt number
     * @param \Exception|\Throwable $error from last execute of the job
     *
     * @return bool
     */
    public function canRetry($attempt, $error) {
        return true;
    }

    /**
     * @param \yii\queue\Queue $queue which pushed and is handling the job
     */
    public function execute($queue) {
        /** @var WebHook[] $targets */
        $targets = WebHook::find()
            ->joinWith('events e', false)
            ->andWhere(['e.event_type' => $this->type])
            ->all();
        foreach ($targets as $target) {
            $job = new DeliveryWebHook();
            $job->type = $this->type;
            $job->url = $target->url;
            $job->secret = $target->secret;
            $job->payloads = $this->payloads;
            Yii::$app->queue->push($job);
        }
    }

}

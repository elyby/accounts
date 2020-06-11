<?php
declare(strict_types=1);

namespace common\tasks;

use common\models\Account;
use common\models\WebHook;
use Yii;
use yii\queue\RetryableJobInterface;

final class CreateWebHooksDeliveries implements RetryableJobInterface {

    public string $type;

    public array $payloads;

    public function __construct(string $type, array $payloads) {
        $this->type = $type;
        $this->payloads = $payloads;
    }

    public static function createAccountEdit(Account $account, array $changedAttributes): self {
        return new static('account.edit', [
            'id' => $account->id,
            'uuid' => $account->uuid,
            'username' => $account->username,
            'email' => $account->email,
            'lang' => $account->lang,
            'isActive' => $account->status === Account::STATUS_ACTIVE,
            'isDeleted' => $account->status === Account::STATUS_DELETED,
            'registered' => date('c', (int)$account->created_at),
            'changedAttributes' => $changedAttributes,
        ]);
    }

    public static function createAccountDeletion(Account $account): self {
        return new static('account.deletion', [
            'id' => $account->id,
            'uuid' => $account->uuid,
            'username' => $account->username,
            'email' => $account->email,
            'registered' => date('c', (int)$account->created_at),
            'deleted' => date('c', (int)$account->deleted_at),
        ]);
    }

    /**
     * @return int time to reserve in seconds
     */
    public function getTtr(): int {
        return 10;
    }

    /**
     * @param int $attempt number
     * @param \Exception|\Throwable $error from last execute of the job
     *
     * @return bool
     */
    public function canRetry($attempt, $error): bool {
        return true;
    }

    /**
     * @param \yii\queue\Queue $queue which pushed and is handling the job
     */
    public function execute($queue): void {
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

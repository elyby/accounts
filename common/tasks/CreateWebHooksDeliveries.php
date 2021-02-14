<?php
declare(strict_types=1);

namespace common\tasks;

use common\models\WebHook;
use common\notifications\NotificationInterface;
use yii\db\Expression;
use yii\queue\RetryableJobInterface;

final class CreateWebHooksDeliveries implements RetryableJobInterface {

    private NotificationInterface $notification;

    public function __construct(NotificationInterface $notification) {
        $this->notification = $notification;
    }

    public function getTtr(): int {
        return 10;
    }

    public function canRetry($attempt, $error): bool {
        return true;
    }

    public function execute($queue): void {
        $type = $this->notification::getType();
        $payloads = $this->notification->getPayloads();

        /** @var WebHook[] $targets */
        $targets = WebHook::find()
            // It's very important to use exactly single quote to begin the string
            // and double quote to specify the string value
            ->andWhere(new Expression("JSON_CONTAINS(`events`, '\"{$type}\"')"))
            ->all();
        foreach ($targets as $target) {
            $job = new DeliveryWebHook();
            $job->type = $type;
            $job->url = $target->url;
            $job->secret = $target->secret;
            $job->payloads = $payloads;
            $queue->push($job);
        }
    }

}

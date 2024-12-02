<?php
declare(strict_types=1);

namespace common\tasks;

use common\models\Account;
use Yii;
use yii\queue\RetryableJobInterface;

final readonly class ClearAccountSessions implements RetryableJobInterface {

    public function __construct(private int $accountId) {
    }

    /**
     * @return int time to reserve in seconds
     */
    public function getTtr(): int {
        return 5 * 60;
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
     * @throws \Exception
     */
    public function execute($queue): void {
        $account = Account::findOne(['id' => $this->accountId]);
        if ($account === null) {
            return;
        }

        /** @var \common\models\AccountSession $authSession */
        foreach ($account->getSessions()->each(100, Yii::$app->unbufferedDb) as $authSession) {
            $authSession->delete();
        }

        /** @var \common\models\OauthSession $oauthSession */
        foreach ($account->getOauthSessions()->each(100, Yii::$app->unbufferedDb) as $oauthSession) {
            $oauthSession->delete();
        }
    }

}

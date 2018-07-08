<?php
declare(strict_types=1);

namespace common\tasks;

use common\models\Account;
use Yii;
use yii\queue\RetryableJobInterface;

class ClearAccountSessions implements RetryableJobInterface {

    public $accountId;

    public static function createFromAccount(Account $account): self {
        $result = new static();
        $result->accountId = $account->id;

        return $result;
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
        $account = Account::findOne($this->accountId);
        if ($account === null) {
            return;
        }

        foreach ($account->getSessions()->each(100, Yii::$app->unbufferedDb) as $authSession) {
            /** @var \common\models\AccountSession $authSession */
            $authSession->delete();
        }

        foreach ($account->getMinecraftAccessKeys()->each(100, Yii::$app->unbufferedDb) as $key) {
            /** @var \common\models\MinecraftAccessKey $key */
            $key->delete();
        }

        foreach ($account->getOauthSessions()->each(100, Yii::$app->unbufferedDb) as $oauthSession) {
            /** @var \common\models\OauthSession $oauthSession */
            $oauthSession->delete();
        }
    }

}

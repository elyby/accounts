<?php
declare(strict_types=1);

namespace common\tasks;

use common\models\Account;
use Yii;
use yii\queue\RetryableJobInterface;

final class DeleteAccount implements RetryableJobInterface {

    public function __construct(private int $accountId) {
    }

    public function getTtr(): int {
        return PHP_INT_MAX; // Let it work as long as it needs to
    }

    public function canRetry($attempt, $error): bool {
        return true;
    }

    /**
     * @param \yii\queue\Queue $queue
     * @throws \Throwable
     */
    public function execute($queue): void {
        $account = Account::findOne(['id' => $this->accountId]);
        if ($account === null || $this->shouldAccountBeDeleted($account) === false) {
            return;
        }

        $transaction = Yii::$app->db->beginTransaction();

        (new ClearAccountSessions($account->id))->execute($queue);
        foreach ($account->oauthClients as $oauthClient) {
            (new ClearOauthSessions($oauthClient->id))->execute($queue);
        }

        /** @var \common\models\EmailActivation $emailActivation */
        foreach ($account->getEmailActivations()->each(100, Yii::$app->unbufferedDb) as $emailActivation) {
            $emailActivation->delete();
        }

        /** @var \common\models\UsernameHistory $usernameHistoryEntry */
        foreach ($account->getUsernameHistory()->each(100, Yii::$app->unbufferedDb) as $usernameHistoryEntry) {
            $usernameHistoryEntry->delete();
        }

        $account->delete();

        $transaction->commit();
    }

    private function shouldAccountBeDeleted(Account $account): bool {
        return $account->status === Account::STATUS_DELETED && $account->getDeleteAt()->subSecond()->isPast();
    }

}

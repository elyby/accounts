<?php
declare(strict_types=1);

namespace common\tasks;

use common\models\OauthClient;
use Yii;
use yii\queue\RetryableJobInterface;

final class ClearOauthSessions implements RetryableJobInterface {

    public string $clientId;

    /**
     * @var int|null unix timestamp, that allows to limit this task to clear only some old sessions
     */
    public ?int $notSince;

    public function __construct(string $clientId, int $notSince = null) {
        $this->clientId = $clientId;
        $this->notSince = $notSince;
    }

    public static function createFromOauthClient(OauthClient $client, int $notSince = null): self {
        return new self($client->id, $notSince);
    }

    public function getTtr(): int {
        return 60/*sec*/ * 5/*min*/;
    }

    public function canRetry($attempt, $error): bool {
        return true;
    }

    /**
     * @param \yii\queue\Queue $queue which pushed and is handling the job
     *
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function execute($queue): void {
        Yii::$app->statsd->inc('queue.clearOauthSessions.attempt');
        /** @var OauthClient|null $client */
        $client = OauthClient::find()
            ->includeDeleted()
            ->andWhere(['id' => $this->clientId])
            ->one();
        if ($client === null) {
            return;
        }

        $sessionsQuery = $client->getSessions();
        if ($this->notSince !== null) {
            $sessionsQuery->andWhere(['<=', 'created_at', $this->notSince]);
        }

        foreach ($sessionsQuery->each(100, Yii::$app->unbufferedDb) as $session) {
            /** @var \common\models\OauthSession $session */
            $session->delete();
        }
    }

}

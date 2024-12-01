<?php
declare(strict_types=1);

namespace common\tasks;

use common\models\Account;
use common\models\MojangUsername;
use Ely\Mojang\Api as MojangApi;
use Ely\Mojang\Exception\MojangApiException;
use Ely\Mojang\Exception\NoContentException;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;
use Yii;
use yii\queue\JobInterface;

final class PullMojangUsername implements JobInterface {

    public $username;

    public static function createFromAccount(Account $account): self {
        $result = new self();
        $result->username = $account->username;

        return $result;
    }

    /**
     * @param \yii\queue\Queue $queue which pushed and is handling the job
     *
     * @throws \Exception
     */
    public function execute($queue): void {
        Yii::$app->statsd->inc('queue.pullMojangUsername.attempt');
        /** @var MojangApi $mojangApi */
        $mojangApi = Yii::$container->get(MojangApi::class);
        try {
            $response = $mojangApi->usernameToUUID($this->username);
            Yii::$app->statsd->inc('queue.pullMojangUsername.found');
        } catch (NoContentException $e) {
            $response = false;
            Yii::$app->statsd->inc('queue.pullMojangUsername.not_found');
        } catch (GuzzleException|MojangApiException $e) {
            Yii::$app->statsd->inc('queue.pullMojangUsername.error');
            return;
        }

        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne($this->username);
        if ($response === false) {
            if ($mojangUsername !== null) {
                $mojangUsername->delete();
            }
        } else {
            if ($mojangUsername === null) {
                $mojangUsername = new MojangUsername();
                $mojangUsername->username = $response->getName();
                $mojangUsername->uuid = $response->getId();
            } else {
                $mojangUsername->uuid = $response->getId();
                $mojangUsername->touch('last_pulled_at');
            }

            Assert::true($mojangUsername->save(), 'Cannot save mojang username');
        }
    }

}

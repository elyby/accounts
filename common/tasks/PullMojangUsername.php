<?php
declare(strict_types=1);

namespace common\tasks;

use api\exceptions\ThisShouldNotHappenException;
use common\components\Mojang\Api as MojangApi;
use common\components\Mojang\exceptions\MojangApiException;
use common\components\Mojang\exceptions\NoContentException;
use common\models\Account;
use common\models\MojangUsername;
use GuzzleHttp\Exception\RequestException;
use Yii;
use yii\queue\JobInterface;

class PullMojangUsername implements JobInterface {

    public $username;

    public static function createFromAccount(Account $account): self {
        $result = new static();
        $result->username = $account->username;

        return $result;
    }

    /**
     * @param \yii\queue\Queue $queue which pushed and is handling the job
     *
     * @throws \Exception
     */
    public function execute($queue) {
        Yii::$app->statsd->inc('queue.pullMojangUsername.attempt');
        $mojangApi = $this->createMojangApi();
        try {
            $response = $mojangApi->usernameToUUID($this->username);
            Yii::$app->statsd->inc('queue.pullMojangUsername.found');
        } catch (NoContentException $e) {
            $response = false;
            Yii::$app->statsd->inc('queue.pullMojangUsername.not_found');
        } catch (RequestException | MojangApiException $e) {
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
                $mojangUsername->username = $response->name;
                $mojangUsername->uuid = $response->id;
            } else {
                $mojangUsername->uuid = $response->id;
                $mojangUsername->touch('last_pulled_at');
            }

            if (!$mojangUsername->save()) {
                throw new ThisShouldNotHappenException('Cannot save mojang username');
            }
        }
    }

    protected function createMojangApi(): MojangApi {
        return new MojangApi();
    }

}

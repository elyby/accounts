<?php
namespace api\modules\session\filters;

use common\models\OauthClient;
use Yii;
use yii\web\Request;
use yii\web\TooManyRequestsHttpException;

class RateLimiter extends \yii\filters\RateLimiter {

    public $limit = 180;
    public $limitTime = 3600; // 1h

    private $server;

    /**
     * @inheritdoc
     */
    public function checkRateLimit($user, $request, $response, $action) {
        $server = $this->getServer($request);
        if ($server !== null) {
            return;
        }

        $ip = $request->getUserIP();
        $key = $this->buildKey($ip);

        $redis = $this->getRedis();
        $countRequests = intval($redis->executeCommand('INCR', [$key]));
        if ($countRequests === 1) {
            $redis->executeCommand('EXPIRE', [$key, $this->limitTime]);
        }

        if ($countRequests > $this->limit) {
            throw new TooManyRequestsHttpException($this->errorMessage);
        }
    }

    /**
     * @return \yii\redis\Connection
     */
    public function getRedis() {
        return Yii::$app->redis;
    }

    /**
     * @param Request $request
     * @return OauthClient|null
     */
    protected function getServer(Request $request) {
        $serverId = $request->get('server_id');
        if ($serverId === null) {
            $this->server = false;
            return null;
        }

        if ($this->server === null) {
            /** @var OauthClient $server */
            $this->server = OauthClient::findOne($serverId);
            // TODO: убедится, что это сервер
            if ($this->server === null) {
                $this->server = false;
            }
        }

        if ($this->server === false) {
            return null;
        }

        return $this->server;
    }

    protected function buildKey($ip) : string {
        return 'sessionserver:ratelimit:' . $ip;
    }

}

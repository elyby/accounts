<?php
namespace api\modules\session\filters;

use common\models\OauthClient;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Request;
use yii\web\TooManyRequestsHttpException;

class RateLimiter extends \yii\filters\RateLimiter {

    public $limit = 180;

    public $limitTime = 3600; // 1h

    public $authserverDomain;

    private $server;

    public function init(): void {
        parent::init();
        if ($this->authserverDomain === null) {
            throw new InvalidConfigException('authserverDomain param is required');
        }
    }

    /**
     * @inheritdoc
     * @throws TooManyRequestsHttpException
     */
    public function beforeAction($action) {
        $this->checkRateLimit(
            null, // @phpstan-ignore argument.type (at this moment we don't have any specific identity, so pass null (yea, it's hacky))
            $this->request ?: Yii::$app->getRequest(),
            $this->response ?: Yii::$app->getResponse(),
            $action,
        );

        return true;
    }

    /**
     * @inheritdoc
     * @throws TooManyRequestsHttpException
     */
    public function checkRateLimit($user, $request, $response, $action): void {
        if (parse_url($request->getHostInfo(), PHP_URL_HOST) === $this->authserverDomain) {
            return;
        }

        $server = $this->getServer($request);
        if ($server !== null) {
            return;
        }

        $ip = $request->getUserIP();
        $key = $this->buildKey($ip);

        $countRequests = (int)Yii::$app->redis->incr($key);
        if ($countRequests === 1) {
            Yii::$app->redis->expire($key, $this->limitTime);
        }

        if ($countRequests > $this->limit) {
            throw new TooManyRequestsHttpException($this->errorMessage);
        }
    }

    protected function getServer(Request $request): ?OauthClient {
        $serverId = $request->get('server_id');
        if ($serverId === null) {
            $this->server = false;
            return null;
        }

        if ($this->server === null) {
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

    protected function buildKey(string $ip): string {
        return 'sessionserver:ratelimit:' . $ip;
    }

}

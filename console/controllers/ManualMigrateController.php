<?php
declare(strict_types=1);

namespace console\controllers;

use common\models\OauthSession;
use Webmozart\Assert\Assert;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class ManualMigrateController extends Controller {

    public function actionOauthSessions(bool $removeKeys = false): int {
        $cursor = 0;
        $totalCount = 0;
        while (true) {
            $response = Yii::$app->redis->scan($cursor, 'MATCH', 'oauth:sessions:*:scopes', 'COUNT', 500);
            $cursor = (int)$response[0];
            $keys = $response[1];
            if (!empty($keys)) {
                $sessionsIds = array_map(function(string $key): int {
                    return (int)explode(':', $key)[2];
                }, $keys);
                /** @var OauthSession[] $sessions */
                $sessions = OauthSession::find()->andWhere(['legacy_id' => $sessionsIds]);
                foreach ($sessions as $session) {
                    if (empty($session->scopes)) {
                        $session->scopes = Yii::$app->redis->smembers("oauth:sessions:{$session->legacy_id}:scopes");
                        Assert::true($session->save());
                    }
                }

                if ($removeKeys) {
                    Yii::$app->redis->del(...$keys);
                }
            }

            $totalCount += count($keys);
            Console::output("Processed {$totalCount} keys.");

            if ($cursor === 0) {
                break;
            }
        }

        return ExitCode::OK;
    }

}

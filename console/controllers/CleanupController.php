<?php
namespace console\controllers;

use common\models\AccountSession;
use common\models\EmailActivation;
use common\models\OauthClient;
use common\tasks\ClearOauthSessions;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class CleanupController extends Controller {

    public function actionEmailKeys(): int {
        $query = EmailActivation::find()
            ->andWhere(['<', 'created_at', time() - 60 * 60 * 24 * 14]); // 14d
        foreach ($query->each(100, Yii::$app->unbufferedDb) as $email) {
            /** @var EmailActivation $email */
            $email->delete();
        }

        return ExitCode::OK;
    }

    /**
     * Sessions that have not been refreshed for 90 days and those
     * that have not been refreshed since they were issued more than 2 weeks ago
     * should be deleted.
     *
     * The AccountSession model doesn't have any relations,
     * so the records can be deleted just with mass delete operation.
     */
    public function actionWebSessions(): int {
        AccountSession::deleteAll([
            'OR',
            ['<', 'last_refreshed_at', time() - 7776000], // 90 days
            [
                'AND',
                'created_at = last_refreshed_at',
                ['<', 'created_at', time() - 1209600], // 2 weeks
            ],
        ]);

        return ExitCode::OK;
    }

    public function actionOauthClients(): int {
        /** @var OauthClient[] $clients */
        $clients = OauthClient::find()
            ->onlyDeleted()
            ->all();
        foreach ($clients as $client) {
            if ($client->getSessions()->exists()) {
                Yii::$app->queue->push(ClearOauthSessions::createFromOauthClient($client));
                continue;
            }

            $client->delete();
        }

        return ExitCode::OK;
    }

}

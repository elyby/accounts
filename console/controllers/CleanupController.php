<?php
namespace console\controllers;

use common\models\AccountSession;
use common\models\EmailActivation;
use common\models\MinecraftAccessKey;
use common\models\OauthClient;
use common\tasks\ClearOauthSessions;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class CleanupController extends Controller {

    public function actionEmailKeys(): int {
        $query = EmailActivation::find();
        foreach ($this->getEmailActivationsDurationsMap() as $typeId => $expiration) {
            $query->orWhere([
                'AND',
                ['type' => $typeId],
                ['<', 'created_at', time() - $expiration],
            ]);
        }

        foreach ($query->each(100, Yii::$app->unbufferedDb) as $email) {
            /** @var EmailActivation $email */
            $email->delete();
        }

        return ExitCode::OK;
    }

    public function actionMinecraftSessions(): int {
        $expiredMinecraftSessionsQuery = MinecraftAccessKey::find()
            ->andWhere(['<', 'updated_at', time() - 1209600]); // 2 weeks

        foreach ($expiredMinecraftSessionsQuery->each(100, Yii::$app->unbufferedDb) as $minecraftSession) {
            /** @var MinecraftAccessKey $minecraftSession */
            $minecraftSession->delete();
        }

        return ExitCode::OK;
    }

    /**
     * Нужно удалить те сессии, которые не рефрешились 90 дней,
     * а также сессии, которые ни разу не рефрешились с момента своей выписки
     * более чем 2 недели назад.
     *
     * У модели AccountSession нет внешних связей, так что целевые записи
     * могут быть удалены без использования циклов.
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

    private function getEmailActivationsDurationsMap(): array {
        $durationsMap = [];
        foreach (EmailActivation::getClassMap() as $typeId => $className) {
            /** @var EmailActivation $object */
            $object = new $className();
            /** @var \common\behaviors\EmailActivationExpirationBehavior $behavior */
            $behavior = $object->getBehavior('expirationBehavior');
            /** @noinspection NullPointerExceptionInspection */
            $expiration = $behavior->expirationTimeout ?? 1123200; // 13d по умолчанию
            // Приращаем 1 день, чтобы пользователи ещё могли получать сообщения об истечении кода активации
            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            $durationsMap[$typeId] = $expiration + 86400;
        }

        return $durationsMap;
    }

}

<?php
namespace console\controllers;

use common\models\AccountSession;
use common\models\EmailActivation;
use common\models\MinecraftAccessKey;
use Generator;
use yii\console\Controller;
use yii\db\ActiveQueryInterface;

class CleanupController extends Controller {

    public function actionEmailKeys() {
        $query = EmailActivation::find();
        foreach ($this->getEmailActivationsDurationsMap() as $typeId => $expiration) {
            $query->orWhere([
                'AND',
                ['type' => $typeId],
                ['<', 'created_at', time() - $expiration],
            ]);
        }

        foreach ($this->each($query) as $email) {
            /** @var EmailActivation $email */
            $email->delete();
        }

        return self::EXIT_CODE_NORMAL;
    }

    public function actionMinecraftSessions() {
        $expiredMinecraftSessionsQuery = MinecraftAccessKey::find()
            ->andWhere(['<', 'updated_at', time() - 1209600]); // 2 weeks

        foreach ($this->each($expiredMinecraftSessionsQuery) as $minecraftSession) {
            /** @var MinecraftAccessKey $minecraftSession */
            $minecraftSession->delete();
        }

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Нужно удалить те сессии, которые не рефрешились 90 дней,
     * а также сессии, которые ни разу не рефрешились с момента своей выписки
     * более чем 2 недели назад.
     *
     * У модели AccountSession нет внешних связей, так что целевые записи
     * могут быть удалены без использования циклов.
     */
    public function actionWebSessions() {
        AccountSession::deleteAll([
            'OR',
            ['<', 'last_refreshed_at', time() - 7776000], // 90 days
            [
                'AND',
                'created_at = last_refreshed_at',
                ['<', 'created_at', time() - 1209600], // 2 weeks
            ],
        ]);

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Each function implementation, that allows you to iterate over values,
     * when in each iteration row removing from database. If you do not remove
     * value in iteration, then this will cause infinite loop.
     *
     * @param ActiveQueryInterface $query
     * @param int                  $size
     *
     * @return Generator
     */
    private function each(ActiveQueryInterface $query, int $size = 100): Generator {
        $query = clone $query;
        $query->limit($size);
        while (true) {
            $rows = $query->all();
            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                yield $row;
            }
        }
    }

    private function getEmailActivationsDurationsMap(): array {
        $durationsMap = [];
        foreach (EmailActivation::getClassMap() as $typeId => $className) {
            /** @var EmailActivation $object */
            $object = new $className;
            /** @var \common\behaviors\EmailActivationExpirationBehavior $behavior */
            $behavior = $object->getBehavior('expirationBehavior');
            $expiration = $behavior->expirationTimeout ?? 1123200; // 13d по умолчанию
            // Приращаем 1 день, чтобы пользователи ещё могли получать сообщения об истечении кода активации
            /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
            $durationsMap[$typeId] = $expiration + 86400;
        }

        return $durationsMap;
    }

}

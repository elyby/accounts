<?php
namespace console\controllers;

use common\models\EmailActivation;
use yii\console\Controller;

class CleanupController extends Controller {

    public function actionEmailKeys() {
        $query = EmailActivation::find();
        $conditions = ['OR'];
        foreach ($this->getEmailActivationsDurationsMap() as $typeId => $expiration) {
            $conditions[] = [
                'AND',
                ['type' => $typeId],
                ['<', 'created_at', time() - $expiration],
            ];
        }

        /** @var EmailActivation[] $expiredEmails */
        $expiredEmails = $query->andWhere($conditions)->all();
        foreach ($expiredEmails as $email) {
            $email->delete();
        }

        return self::EXIT_CODE_NORMAL;
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

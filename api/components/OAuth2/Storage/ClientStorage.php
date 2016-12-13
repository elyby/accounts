<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\ClientEntity;
use api\components\OAuth2\Entities\SessionEntity;
use common\models\OauthClient;
use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\ClientInterface;
use yii\helpers\StringHelper;

class ClientStorage extends AbstractStorage implements ClientInterface {

    const REDIRECT_STATIC_PAGE = 'static_page';
    const REDIRECT_STATIC_PAGE_WITH_CODE = 'static_page_with_code';

    /**
     * @inheritdoc
     */
    public function get($clientId, $clientSecret = null, $redirectUri = null, $grantType = null) {
        $query = OauthClient::find()->andWhere(['id' => $clientId]);
        if ($clientSecret !== null) {
            $query->andWhere(['secret' => $clientSecret]);
        }

        /** @var OauthClient|null $model */
        $model = $query->one();
        if ($model === null) {
            return null;
        }

        // TODO: нужно учитывать тип приложения
        /*
         * Для приложений типа "настольный" redirect_uri необязателем - он должен быть по умолчанию равен
         * статичному редиректу на страницу сайта
         * А для приложений типа "сайт" редирект должен быть всегда.
         * Короче это нужно учесть
         */
        if ($redirectUri !== null) {
            if (in_array($redirectUri, [self::REDIRECT_STATIC_PAGE, self::REDIRECT_STATIC_PAGE_WITH_CODE], true)) {
                // Тут, наверное, нужно проверить тип приложения
            } else {
                if (!StringHelper::startsWith($redirectUri, $model->redirect_uri, false)) {
                    return null;
                }
            }
        }

        $entity = $this->hydrate($model);
        $entity->setRedirectUri($redirectUri);

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function getBySession(OriginalSessionEntity $session) {
        if (!$session instanceof SessionEntity) {
            throw new \ErrorException('This module assumes that $session typeof ' . SessionEntity::class);
        }

        /** @var OauthClient|null $model */
        $model = OauthClient::findOne($session->getClientId());
        if ($model === null) {
            return null;
        }

        return $this->hydrate($model);
    }

    private function hydrate(OauthClient $model) : ClientEntity {
        $entity = new ClientEntity($this->server);
        $entity->setId($model->id);
        $entity->setName($model->name);
        $entity->setSecret($model->secret);
        $entity->setRedirectUri($model->redirect_uri);

        return $entity;
    }

}

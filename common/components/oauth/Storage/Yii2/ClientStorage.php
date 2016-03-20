<?php
namespace common\components\oauth\Storage\Yii2;

use common\components\oauth\Entity\SessionEntity;
use common\models\OauthClient;
use League\OAuth2\Server\Entity\ClientEntity;
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
        $query = OauthClient::find()
            ->select(['id', 'name', 'secret', 'redirect_uri'])
            ->where([OauthClient::tableName() . '.id' => $clientId]);

        if ($clientSecret !== null) {
            $query->andWhere(['secret' => $clientSecret]);
        }

        $model = $query->asArray()->one();
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
            if ($redirectUri === self::REDIRECT_STATIC_PAGE || $redirectUri === self::REDIRECT_STATIC_PAGE_WITH_CODE) {
                // Тут, наверное, нужно проверить тип приложения
            } else {
                if (!StringHelper::startsWith($redirectUri, $model['redirect_uri'], false)) {
                    return null;
                }
            }
        }

        $entity = new ClientEntity($this->server);
        $entity->hydrate([
            'id' => $model['id'],
            'name' => $model['name'],
            'secret' => $model['secret'],
            'redirectUri' => $redirectUri,
        ]);

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function getBySession(OriginalSessionEntity $session) {
        if (!$session instanceof SessionEntity) {
            throw new \ErrorException('This module assumes that $session typeof ' . SessionEntity::class);
        }

        $model = OauthClient::find()
            ->select(['id', 'name'])
            ->andWhere(['id' => $session->getClientId()])
            ->asArray()
            ->one();

        if ($model === null) {
            return null;
        }

        return (new ClientEntity($this->server))->hydrate($model);
    }

}

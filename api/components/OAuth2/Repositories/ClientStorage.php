<?php
namespace api\components\OAuth2\Repositories;

use api\components\OAuth2\Entities\ClientEntity;
use api\components\OAuth2\Entities\SessionEntity;
use common\models\OauthClient;
use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\ClientInterface;
use yii\helpers\StringHelper;

class ClientStorage extends AbstractStorage implements ClientInterface {

    private const REDIRECT_STATIC_PAGE = 'static_page';
    private const REDIRECT_STATIC_PAGE_WITH_CODE = 'static_page_with_code';

    /**
     * @inheritdoc
     */
    public function get($clientId, $clientSecret = null, $redirectUri = null, $grantType = null) {
        $model = $this->findClient($clientId);
        if ($model === null) {
            return null;
        }

        if ($clientSecret !== null && $clientSecret !== $model->secret) {
            return null;
        }

        // TODO: should check application type
        //       For "desktop" app type redirect_uri is not required and should be by default set
        //       to the static redirect, but for "site" it's required always.
        if ($redirectUri !== null) {
            if (in_array($redirectUri, [self::REDIRECT_STATIC_PAGE, self::REDIRECT_STATIC_PAGE_WITH_CODE], true)) {
                // I think we should check the type of application here
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

        $model = $this->findClient($session->getClientId());
        if ($model === null) {
            return null;
        }

        return $this->hydrate($model);
    }

    private function hydrate(OauthClient $model): ClientEntity {
        $entity = new ClientEntity($this->server);
        $entity->setId($model->id);
        $entity->setName($model->name);
        $entity->setSecret($model->secret);
        $entity->setIsTrusted($model->is_trusted);
        $entity->setRedirectUri($model->redirect_uri);

        return $entity;
    }

    private function findClient(string $clientId): ?OauthClient {
        return OauthClient::findOne($clientId);
    }

}

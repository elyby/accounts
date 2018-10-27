<?php
declare(strict_types=1);

namespace common\rbac\rules;

use common\models\OauthClient;
use common\rbac\Permissions as P;
use Yii;
use yii\rbac\Rule;

class OauthClientOwner extends Rule {

    public $name = 'oauth_client_owner';

    /**
     * Accepts 2 params:
     * - clientId - it's the client id, that user want access to.
     * - accountId - if it is passed to check the VIEW_OAUTH_CLIENTS permission, then it will
     *               check, that current user have access to the provided account.
     *
     * @param string|int     $accessToken
     * @param \yii\rbac\Item $item
     * @param array          $params
     *
     * @return bool a value indicating whether the rule permits the auth item it is associated with.
     */
    public function execute($accessToken, $item, $params): bool {
        $accountId = $params['accountId'] ?? null;
        if ($accountId !== null && $item->name === P::VIEW_OWN_OAUTH_CLIENTS) {
            return (new AccountOwner())->execute($accessToken, $item, ['accountId' => $accountId]);
        }

        $clientId = $params['clientId'] ?? null;
        if ($clientId === null) {
            return false;
        }

        /** @var OauthClient|null $client */
        $client = OauthClient::findOne($clientId);
        if ($client === null) {
            return true;
        }

        $identity = Yii::$app->user->findIdentityByAccessToken($accessToken);
        if ($identity === null) {
            return false;
        }

        $account = $identity->getAccount();
        if ($account === null) {
            return false;
        }

        if ($account->id !== $client->account_id) {
            return false;
        }

        return true;
    }

}

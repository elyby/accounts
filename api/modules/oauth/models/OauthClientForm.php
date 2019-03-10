<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use api\exceptions\ThisShouldNotHappenException;
use api\modules\oauth\exceptions\InvalidOauthClientState;
use common\models\OauthClient;
use common\tasks\ClearOauthSessions;
use Yii;
use yii\helpers\Inflector;

class OauthClientForm {

    /**
     * @var OauthClient
     */
    private $client;

    public function __construct(OauthClient $client) {
        if ($client->type === null) {
            throw new InvalidOauthClientState('client\'s type field must be set');
        }

        $this->client = $client;
    }

    public function getClient(): OauthClient {
        return $this->client;
    }

    public function save(OauthClientTypeForm $form): bool {
        if (!$form->validate()) {
            return false;
        }

        $client = $this->getClient();
        $form->applyToClient($client);

        if ($client->isNewRecord) {
            $baseId = $id = substr(Inflector::slug($client->name), 0, 250);
            $i = 0;
            while ($this->isClientExists($id)) {
                $id = $baseId . ++$i;
            }

            $client->id = $id;
            $client->generateSecret();
        }

        if (!$client->save()) {
            throw new ThisShouldNotHappenException('Cannot save oauth client');
        }

        return true;
    }

    public function delete(): bool {
        $transaction = Yii::$app->db->beginTransaction();

        $client = $this->client;
        $client->is_deleted = true;
        if (!$client->save()) {
            throw new ThisShouldNotHappenException('Cannot update oauth client');
        }

        Yii::$app->queue->push(ClearOauthSessions::createFromOauthClient($client));

        $transaction->commit();

        return true;
    }

    public function reset(bool $regenerateSecret = false): bool {
        $transaction = Yii::$app->db->beginTransaction();

        $client = $this->client;
        if ($regenerateSecret) {
            $client->generateSecret();
            if (!$client->save()) {
                throw new ThisShouldNotHappenException('Cannot update oauth client');
            }
        }

        Yii::$app->queue->push(ClearOauthSessions::createFromOauthClient($client, time()));

        $transaction->commit();

        return true;
    }

    protected function isClientExists(string $id): bool {
        return OauthClient::find()
            ->includeDeleted()
            ->andWhere(['id' => $id])
            ->exists();
    }

}

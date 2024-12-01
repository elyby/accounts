<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use api\modules\oauth\exceptions\InvalidOauthClientState;
use common\models\OauthClient;
use common\tasks\ClearOauthSessions;
use Webmozart\Assert\Assert;
use Yii;
use yii\helpers\Inflector;

class OauthClientForm {

    private readonly OauthClient $client;

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

        Assert::true($client->save(), 'Cannot save oauth client');

        return true;
    }

    public function delete(): bool {
        $transaction = Yii::$app->db->beginTransaction();

        $client = $this->getClient();
        $client->is_deleted = true;
        Assert::true($client->save(), 'Cannot update oauth client');

        Yii::$app->queue->push(ClearOauthSessions::createFromOauthClient($client));

        $transaction->commit();

        return true;
    }

    public function reset(bool $regenerateSecret = false): bool {
        $transaction = Yii::$app->db->beginTransaction();

        $client = $this->getClient();
        if ($regenerateSecret) {
            $client->generateSecret();
            Assert::true($client->save(), 'Cannot update oauth client');
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

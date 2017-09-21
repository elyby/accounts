<?php
namespace api\modules\session\models;

use common\models\Account;
use Yii;

class SessionModel {

    const KEY_TIME = 120; // 2 min

    public $username;

    public $serverId;

    public function __construct(string $username, string $serverId) {
        $this->username = $username;
        $this->serverId = $serverId;
    }

    public static function find(string $username, string $serverId): ?self {
        $key = static::buildKey($username, $serverId);
        $result = Yii::$app->redis->executeCommand('GET', [$key]);
        if (!$result) {
            return null;
        }

        $data = json_decode($result, true);

        return new static($data['username'], $data['serverId']);
    }

    public function save() {
        $key = static::buildKey($this->username, $this->serverId);
        $data = json_encode([
            'username' => $this->username,
            'serverId' => $this->serverId,
        ]);

        return Yii::$app->redis->executeCommand('SETEX', [$key, self::KEY_TIME, $data]);
    }

    public function delete() {
        return Yii::$app->redis->executeCommand('DEL', [static::buildKey($this->username, $this->serverId)]);
    }

    public function getAccount(): ?Account {
        return Account::findOne(['username' => $this->username]);
    }

    protected static function buildKey($username, $serverId): string {
        return md5('minecraft:join-server:' . mb_strtolower($username) . ':' . $serverId);
    }

}

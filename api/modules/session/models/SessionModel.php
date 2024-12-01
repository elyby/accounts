<?php
declare(strict_types=1);

namespace api\modules\session\models;

use common\models\Account;
use Yii;

final readonly class SessionModel {

    private const int KEY_TIME = 120; // 2 min

    public function __construct(
        public string $username,
        public string $serverId,
    ) {
    }

    public static function find(string $username, string $serverId): ?self {
        $key = self::buildKey($username, $serverId);
        $result = Yii::$app->redis->get($key);
        if (!$result) {
            return null;
        }

        $data = json_decode($result, true);

        return new self($data['username'], $data['serverId']);
    }

    public function save(): mixed {
        $key = self::buildKey($this->username, $this->serverId);
        $data = json_encode([
            'username' => $this->username,
            'serverId' => $this->serverId,
        ]);

        return Yii::$app->redis->setex($key, self::KEY_TIME, $data);
    }

    public function delete(): mixed {
        return Yii::$app->redis->del(self::buildKey($this->username, $this->serverId));
    }

    public function getAccount(): ?Account {
        return Account::findOne(['username' => $this->username]);
    }

    protected static function buildKey(string $username, string $serverId): string {
        return md5('minecraft:join-server:' . mb_strtolower($username) . ':' . $serverId);
    }

}

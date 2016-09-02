<?php
namespace api\modules\session\models;

use Yii;

class SessionModel {

    const KEY_TIME = 120; // 2 min

    public $username;

    public $serverId;

    public function __construct(string $username, string $serverId) {
        $this->username = $username;
        $this->serverId = $serverId;
    }

    /**
     * @param $username
     * @param $serverId
     *
     * @return static|null
     */
    public static function find($username, $serverId) {
        $key = static::buildKey($username, $serverId);
        $result = Yii::$app->redis->executeCommand('GET', [$key]);
        if (!$result) {
            /** @noinspection PhpIncompatibleReturnTypeInspection шторм что-то сума сходит, когда видит static */
            return null;
        }

        $data = json_decode($result, true);
        $model = new static($data['username'], $data['serverId']);

        return $model;
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

    protected static function buildKey($username, $serverId) {
        return md5('minecraft:join-server:' . mb_strtolower($username) . ':' . $serverId);
    }

}

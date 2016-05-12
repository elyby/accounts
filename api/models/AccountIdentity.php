<?php
namespace api\models;

use common\models\Account;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

/**
 * @method static findIdentityByAccessToken($token, $type = null) этот метод реализуется в UserTrait, который
 * подключён в родительском Account и позволяет выполнить условия интерфейса
 * @method string getId() метод реализован в родительском классе, т.к. UserTrait требует, чтобы этот метод
 * присутствовал обязательно, но при этом не навязывает его как абстрактный
 */
class AccountIdentity extends Account implements IdentityInterface {

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        throw new NotSupportedException('This method used for cookie auth, except we using JWT tokens');
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

}

<?php
namespace common\models;

use common\components\UserPass;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use const common\LATEST_RULES_VERSION;

/**
 * Поля модели:
 * @property integer $id
 * @property string  $uuid
 * @property string  $username
 * @property string  $email
 * @property string  $password_hash
 * @property integer $password_hash_strategy
 * @property string  $lang
 * @property integer $status
 * @property integer $rules_agreement_version
 * @property string  $registration_ip
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $password_changed_at
 *
 * Геттеры-сеттеры:
 * @property string  $password пароль пользователя (только для записи)
 * @property string  $profileLink ссылка на профиль на Ely без поддержки static url (только для записи)
 *
 * Отношения:
 * @property EmailActivation[] $emailActivations
 * @property OauthSession[]    $oauthSessions
 * @property UsernameHistory[] $usernameHistory
 * @property AccountSession[]  $sessions
 *
 * Поведения:
 * @mixin TimestampBehavior
 */
class Account extends ActiveRecord {

    const STATUS_DELETED = -10;
    const STATUS_BANNED = -1;
    const STATUS_REGISTERED = 0;
    const STATUS_ACTIVE = 10;

    const PASS_HASH_STRATEGY_OLD_ELY = 0;
    const PASS_HASH_STRATEGY_YII2 = 1;

    public static function tableName() {
        return '{{%accounts}}';
    }

    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * Validates password
     *
     * @param string  $password password to validate
     * @param integer $passwordHashStrategy
     *
     * @return bool if password provided is valid for current user
     * @throws InvalidConfigException
     */
    public function validatePassword($password, $passwordHashStrategy = NULL) : bool {
        if ($passwordHashStrategy === NULL) {
            $passwordHashStrategy = $this->password_hash_strategy;
        }

        switch($passwordHashStrategy) {
            case self::PASS_HASH_STRATEGY_OLD_ELY:
                $hashedPass = UserPass::make($this->email, $password);
                return $hashedPass === $this->password_hash;

            case self::PASS_HASH_STRATEGY_YII2:
                return Yii::$app->security->validatePassword($password, $this->password_hash);

            default:
                throw new InvalidConfigException('You must set valid password_hash_strategy before you can validate password');
        }
    }

    /**
     * @param string $password
     * @throws InvalidConfigException
     */
    public function setPassword($password) {
        $this->password_hash_strategy = self::PASS_HASH_STRATEGY_YII2;
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
        $this->password_changed_at = time();
    }

    public function getEmailActivations() {
        return $this->hasMany(EmailActivation::class, ['account_id' => 'id']);
    }

    public function getOauthSessions() {
        return $this->hasMany(OauthSession::class, ['owner_id' => 'id']);
    }

    public function getUsernameHistory() {
        return $this->hasMany(UsernameHistory::class, ['account_id' => 'id']);
    }

    public function getSessions() {
        return $this->hasMany(AccountSession::class, ['account_id' => 'id']);
    }

    /**
     * Выполняет проверку, принадлежит ли этому нику аккаунт у Mojang
     *
     * @return bool
     */
    public function hasMojangUsernameCollision() : bool {
        return MojangUsername::find()
            ->andWhere(['username' => $this->username])
            ->exists();
    }

    /**
     * Т.к. у нас нет инфы по static_url пользователя, то пока генерируем самый простой вариант
     * с ссылкой на профиль по id. На Ely он всё равно редиректнется на static, а мы так или
     * иначе обеспечим отдачу этой инфы.
     *
     * @return string
     */
    public function getProfileLink() : string {
        return 'http://ely.by/u' . $this->id;
    }

    /**
     * При создании структуры БД все аккаунты получают null значение в это поле, однако оно
     * обязательно для заполнения. Все мигрировавшие с Ely аккаунты будут иметь null значение,
     * а актуальной версией будет 1 версия правил сайта (т.к. раньше их просто не было). Ну а
     * дальше уже будем инкрементить.
     *
     * @return bool
     */
    public function isAgreedWithActualRules() : bool {
        return $this->rules_agreement_version === LATEST_RULES_VERSION;
    }

    public function setRegistrationIp($ip) {
        $this->registration_ip = $ip === null ? null : inet_pton($ip);
    }

    public function getRegistrationIp() {
        return $this->registration_ip === null ? null : inet_ntop($this->registration_ip);
    }

}

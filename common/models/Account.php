<?php
namespace common\models;

use common\helpers\Error as E;
use common\components\UserPass;
use common\validators\LanguageValidator;
use Ely\Yii2\TempmailValidator;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

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
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $password_changed_at
 *
 * Геттеры-сеттеры:
 * @property string            $password пароль пользователя (только для записи)
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

    public function rules() {
        return [
            [['username'], 'filter', 'filter' => 'trim'],
            [['username'], 'required', 'message' => E::USERNAME_REQUIRED],
            [['username'], 'string', 'min' => 3, 'max' => 21,
                'tooShort' => E::USERNAME_TOO_SHORT,
                'tooLong' => E::USERNAME_TOO_LONG,
            ],
            [['username'], 'match', 'pattern' => '/^[\p{L}\d-_\.!?#$%^&*()\[\]:;]+$/u',
                'message' => E::USERNAME_INVALID,
            ],
            [['username'], 'unique', 'message' => E::USERNAME_NOT_AVAILABLE],

            [['email'], 'filter', 'filter' => 'trim'],
            [['email'], 'required', 'message' => E::EMAIL_REQUIRED],
            [['email'], 'string', 'max' => 255, 'tooLong' => E::EMAIL_TOO_LONG],
            [['email'], 'email', 'checkDNS' => true, 'enableIDN' => true, 'message' => E::EMAIL_INVALID],
            [['email'], TempmailValidator::class, 'message' => E::EMAIL_IS_TEMPMAIL],
            [['email'], 'unique', 'message' => E::EMAIL_NOT_AVAILABLE],

            [['lang'], LanguageValidator::class],
            [['lang'], 'default', 'value' => 'en'],
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
    public function validatePassword($password, $passwordHashStrategy = NULL) {
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
     * Метод проверяет, может ли текущий пользователь быть автоматически авторизован
     * для указанного клиента без запроса доступа к необходимому списку прав
     *
     * @param OauthClient $client
     * @param \League\OAuth2\Server\Entity\ScopeEntity[] $scopes
     *
     * TODO: этому методу здесь не место.
     *
     * @return bool
     */
    public function canAutoApprove(OauthClient $client, array $scopes = []) {
        if ($client->is_trusted) {
            return true;
        }

        /** @var OauthSession|null $session */
        $session = $this->getOauthSessions()->andWhere(['client_id' => $client->id])->one();
        if ($session !== null) {
            $existScopes = $session->getScopes()->members();
            if (empty(array_diff(array_keys($scopes), $existScopes))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Выполняет проверку, принадлежит ли этому нику аккаунт у Mojang
     * @return bool
     */
    public function hasMojangUsernameCollision() {
        return MojangUsername::find()
            ->andWhere(['username' => $this->username])
            ->exists();
    }

}

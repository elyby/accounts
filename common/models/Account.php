<?php
declare(strict_types=1);

namespace common\models;

use common\components\UserPass;
use common\tasks\CreateWebHooksDeliveries;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use const common\LATEST_RULES_VERSION;

/**
 * Fields:
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
 * @property string  $otp_secret
 * @property integer $is_otp_enabled
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $password_changed_at
 *
 * Getters-setters:
 * @property-write string $password plain user's password
 * @property-read string $profileLink link to the user's Ely.by profile
 *
 * Relations:
 * @property EmailActivation[]    $emailActivations
 * @property OauthSession[]       $oauthSessions
 * @property OauthClient[]        $oauthClients
 * @property UsernameHistory[]    $usernameHistory
 * @property AccountSession[]     $sessions
 * @property MinecraftAccessKey[] $minecraftAccessKeys
 *
 * Behaviors:
 * @mixin TimestampBehavior
 */
class Account extends ActiveRecord {

    public const STATUS_DELETED = -10;
    public const STATUS_BANNED = -1;
    public const STATUS_REGISTERED = 0;
    public const STATUS_ACTIVE = 10;

    public const PASS_HASH_STRATEGY_OLD_ELY = 0;
    public const PASS_HASH_STRATEGY_YII2 = 1;

    public static function tableName(): string {
        return '{{%accounts}}';
    }

    public function behaviors(): array {
        return [
            TimestampBehavior::class,
        ];
    }

    public function validatePassword(string $password, int $passwordHashStrategy = null): bool {
        if ($passwordHashStrategy === null) {
            $passwordHashStrategy = $this->password_hash_strategy;
        }

        switch ($passwordHashStrategy) {
            case self::PASS_HASH_STRATEGY_OLD_ELY:
                return UserPass::make($this->email, $password) === $this->password_hash;

            case self::PASS_HASH_STRATEGY_YII2:
                return Yii::$app->security->validatePassword($password, $this->password_hash);

            default:
                throw new InvalidConfigException('You must set valid password_hash_strategy before you can validate password');
        }
    }

    public function setPassword(string $password): void {
        $this->password_hash_strategy = self::PASS_HASH_STRATEGY_YII2;
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
        $this->password_changed_at = time();
    }

    public function getEmailActivations(): ActiveQuery {
        return $this->hasMany(EmailActivation::class, ['account_id' => 'id']);
    }

    public function getOauthSessions(): ActiveQuery {
        return $this->hasMany(OauthSession::class, ['account_id' => 'id']);
    }

    public function getOauthClients(): OauthClientQuery {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(OauthClient::class, ['account_id' => 'id']);
    }

    public function getUsernameHistory(): ActiveQuery {
        return $this->hasMany(UsernameHistory::class, ['account_id' => 'id']);
    }

    public function getSessions(): ActiveQuery {
        return $this->hasMany(AccountSession::class, ['account_id' => 'id']);
    }

    public function getMinecraftAccessKeys(): ActiveQuery {
        return $this->hasMany(MinecraftAccessKey::class, ['account_id' => 'id']);
    }

    public function hasMojangUsernameCollision(): bool {
        return MojangUsername::find()
            ->andWhere(['username' => $this->username])
            ->exists();
    }

    /**
     * Since we don't have info about the user's static_url, we still generate the simplest
     * version with a link to the profile by it's id. On Ely.by, it will be redirected to static url.
     *
     * @return string
     */
    public function getProfileLink(): string {
        return 'http://ely.by/u' . $this->id;
    }

    /**
     * Initially, the table of users we got from the main site, where there were no rules.
     * All existing users at the time of migration received an empty value in this field.
     * They will have to confirm their agreement with the rules at the first login.
     * All new users automatically agree with the current version of the rules.
     *
     * @return bool
     */
    public function isAgreedWithActualRules(): bool {
        return $this->rules_agreement_version === LATEST_RULES_VERSION;
    }

    public function setRegistrationIp($ip): void {
        $this->registration_ip = $ip === null ? null : inet_pton($ip);
    }

    public function getRegistrationIp(): ?string {
        return $this->registration_ip === null ? null : inet_ntop($this->registration_ip);
    }

    public function afterSave($insert, $changedAttributes) {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            return;
        }

        $meaningfulFields = ['username', 'email', 'uuid', 'status', 'lang'];
        $meaningfulChangedAttributes = array_filter($changedAttributes, function(string $key) use ($meaningfulFields) {
            return in_array($key, $meaningfulFields, true);
        }, ARRAY_FILTER_USE_KEY);
        if (empty($meaningfulChangedAttributes)) {
            return;
        }

        Yii::$app->queue->push(CreateWebHooksDeliveries::createAccountEdit($this, $meaningfulChangedAttributes));
    }

}

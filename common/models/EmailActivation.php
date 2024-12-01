<?php
declare(strict_types=1);

namespace common\models;

use common\behaviors\PrimaryKeyValueBehavior;
use common\components\UserFriendlyRandomKey;
use DateInterval;
use DateTimeImmutable;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Fields:
 * @property string     $key
 * @property int        $account_id
 * @property int        $type
 * @property array|null $data
 * @property int        $created_at
 *
 * Relations:
 * @property Account $account
 *
 * Behaviors:
 * @mixin TimestampBehavior
 */
class EmailActivation extends ActiveRecord {

    public const TYPE_REGISTRATION_EMAIL_CONFIRMATION = 0;
    public const TYPE_FORGOT_PASSWORD_KEY = 1;
    public const TYPE_CURRENT_EMAIL_CONFIRMATION = 2;
    public const TYPE_NEW_EMAIL_CONFIRMATION = 3;

    public static function tableName(): string {
        return 'email_activations';
    }

    /**
     * @return array<self::TYPE_*, class-string<\common\models\EmailActivation>>
     */
    public static function getClassMap(): array {
        return [
            self::TYPE_REGISTRATION_EMAIL_CONFIRMATION => confirmations\RegistrationConfirmation::class,
            self::TYPE_FORGOT_PASSWORD_KEY => confirmations\ForgotPassword::class,
            self::TYPE_CURRENT_EMAIL_CONFIRMATION => confirmations\CurrentEmailConfirmation::class,
            self::TYPE_NEW_EMAIL_CONFIRMATION => confirmations\NewEmailConfirmation::class,
        ];
    }

    public static function instantiate($row): static {
        $type = ArrayHelper::getValue($row, 'type');
        if ($type === null) {
            return parent::instantiate($row);
        }

        $className = self::getClassMap()[$type] ?? throw new InvalidConfigException('Unexpected type');

        // @phpstan-ignore return.type (the type is correct, but it seems like it must be fixed within Yii2-extension)
        return new $className();
    }

    public static function find(): EmailActivationQuery {
        return new EmailActivationQuery(static::class);
    }

    public function behaviors(): array {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
            [
                'class' => PrimaryKeyValueBehavior::class,
                'value' => function(): string {
                    return UserFriendlyRandomKey::make();
                },
            ],
        ];
    }

    public function getAccount(): AccountQuery {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function canResend(): bool {
        $timeout = $this->getResendTimeout();
        if ($timeout === null) {
            return true;
        }

        return $this->compareTime($timeout);
    }

    public function canResendAt(): DateTimeImmutable {
        return $this->calculateTime($this->getResendTimeout() ?? new DateInterval('PT0S'));
    }

    public function isStale(): bool {
        $duration = $this->getExpireDuration();
        if ($duration === null) {
            return false;
        }

        return $this->compareTime($duration);
    }

    /**
     * After which time the message for this action type can be resended.
     * When null returned the message can be sent immediately.
     *
     * @return DateInterval|null
     */
    protected function getResendTimeout(): ?DateInterval {
        return new DateInterval('PT5M');
    }

    /**
     * How long the activation code should be valid.
     * When null returned the code is never expires
     *
     * @return DateInterval|null
     */
    protected function getExpireDuration(): ?DateInterval {
        return null;
    }

    private function compareTime(DateInterval $value): bool {
        return (new DateTimeImmutable()) > $this->calculateTime($value);
    }

    private function calculateTime(DateInterval $interval): DateTimeImmutable {
        /** @noinspection PhpUnhandledExceptionInspection */
        return (new DateTimeImmutable('@' . $this->created_at))->add($interval);
    }

}

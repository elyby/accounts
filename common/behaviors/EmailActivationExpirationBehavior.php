<?php
namespace common\behaviors;

use yii\base\Behavior;

/**
 * @property \common\models\EmailActivation $owner
 */
class EmailActivationExpirationBehavior extends Behavior {

    /**
     * @var int the number of seconds before the code can be sent again
     * @see EmailActivation::canRepeat()
     */
    public $repeatTimeout;

    /**
     * @var int the number of seconds before this activation expires
     * @see EmailActivation::isExpired()
     */
    public $expirationTimeout;

    /**
     * Is it allowed to resend a message of the current type?
     * The value of EmailActivation::$repeatTimeout is used for checking as follows:
     * - <0 will forbid you to resend this activation
     * - =0 allows you to send messages at any time
     * - >0 will check how many seconds have passed since the model was created
     *
     * @see EmailActivation::compareTime()
     * @return bool
     */
    public function canRepeat(): bool {
        return $this->compareTime($this->repeatTimeout);
    }

    /**
     * Did the code expire?
     * The value of EmailActivation::$expirationTimeout is used for checking as follows:
     * - <0 means the code will never expire
     * - =0 will always say that the code has expired
     * - >0 will check how many seconds have passed since the model was created
     *
     * @see EmailActivation::compareTime()
     * @return bool
     */
    public function isExpired(): bool {
        return $this->compareTime($this->expirationTimeout);
    }

    public function canRepeatIn(): int {
        return $this->calculateTime($this->repeatTimeout);
    }

    public function expireIn(): int {
        return $this->calculateTime($this->expirationTimeout);
    }

    private function compareTime(int $value): bool {
        if ($value < 0) {
            return false;
        }

        if ($value === 0) {
            return true;
        }

        return time() > $this->calculateTime($value);
    }

    private function calculateTime(int $value): int {
        return $this->owner->created_at + $value;
    }

}

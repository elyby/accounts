<?php
namespace common\behaviors;

use yii\base\Behavior;

/**
 * @property \common\models\EmailActivation $owner
 */
class EmailActivationExpirationBehavior extends Behavior {

    /**
     * @var int количество секунд, прежде чем можно будет повторить отправку кода
     * @see EmailActivation::canRepeat()
     */
    public $repeatTimeout;

    /**
     * @var int количество секунд, прежде чем это подтверждение истечёт
     * @see EmailActivation::isExpired()
     */
    public $expirationTimeout;

    /**
     * Можно ли повторить отправку письма текущего типа?
     * Для проверки используется значение EmailActivation::$repeatTimeout и интерпретируется как:
     * - <0 запретит повторную отправку этого кода
     * - =0 позволит отправлять сообщения в любой момент
     * - >0 будет проверять, сколько секунд прошло с момента создания модели
     *
     * @see EmailActivation::compareTime()
     * @return bool
     */
    public function canRepeat(): bool {
        return $this->compareTime($this->repeatTimeout);
    }

    /**
     * Истёк ли срок кода?
     * Для проверки используется значение EmailActivation::$expirationTimeout и интерпретируется как:
     * - <0 означает, что код никогда не истечёт
     * - =0 всегда будет говорить, что код истёк
     * - >0 будет проверять, сколько секунд прошло с момента создания модели
     *
     * @see EmailActivation::compareTime()
     * @return bool
     */
    public function isExpired(): bool {
        return $this->compareTime($this->expirationTimeout);
    }

    /**
     * Вычисляет, во сколько можно будет выполнить повторную отправку кода
     *
     * @return int
     */
    public function canRepeatIn(): int {
        return $this->calculateTime($this->repeatTimeout);
    }

    /**
     * Вычисляет, во сколько код истечёт
     *
     * @return int
     */
    public function expireIn(): int {
        return $this->calculateTime($this->expirationTimeout);
    }

    protected function compareTime(int $value): bool {
        if ($value < 0) {
            return false;
        }

        if ($value === 0) {
            return true;
        }

        return time() > $this->calculateTime($value);
    }

    protected function calculateTime(int $value): int {
        return $this->owner->created_at + $value;
    }

}

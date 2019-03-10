<?php
namespace common\behaviors;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * @property ActiveRecord $owner
 */
class PrimaryKeyValueBehavior extends Behavior {

    /**
     * @var callable Функция, что будет вызвана для генерации ключа.
     * Должна возвращать случайное значение, подходящее для логики модели. Функция будет вызываться
     * в цикле do-while с целью избежания дубликатов строк по первичному ключу, так что если функция
     * станет возвращать статичное значение, то программа зациклится и что-нибудь здохнет. Не делайте так.
     */
    public $value;

    public function events(): array {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'setPrimaryKeyValue',
        ];
    }

    public function setPrimaryKeyValue(): bool {
        if ($this->owner->getPrimaryKey() === null) {
            $this->refreshPrimaryKeyValue();
        }

        return true;
    }

    public function refreshPrimaryKeyValue(): void {
        do {
            $key = $this->generateValue();
        } while ($this->isValueExists($key));

        $this->owner->{$this->getPrimaryKeyName()} = $key;
    }

    protected function generateValue(): string {
        return (string)call_user_func($this->value);
    }

    protected function isValueExists(string $key): bool {
        $owner = $this->owner;
        return $owner::find()->andWhere([$this->getPrimaryKeyName() => $key])->exists();
    }

    protected function getPrimaryKeyName(): string {
        $owner = $this->owner;
        $primaryKeys = $owner::primaryKey();
        if (!isset($primaryKeys[0])) {
            throw new InvalidConfigException('"' . get_class($owner) . '" must have a primary key.');
        }

        if (count($primaryKeys) > 1) {
            throw new InvalidConfigException('Current behavior don\'t support models with more then one primary key.');
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection да как бы оно всё нормально, но шторм мне не верит */
        return $primaryKeys[0];
    }

}

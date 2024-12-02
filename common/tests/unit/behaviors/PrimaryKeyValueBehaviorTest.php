<?php
declare(strict_types=1);

namespace common\tests\unit\behaviors;

use common\behaviors\PrimaryKeyValueBehavior;
use common\tests\unit\TestCase;
use yii\db\ActiveRecord;

class PrimaryKeyValueBehaviorTest extends TestCase {

    public function testGenerateValueForThePrimaryKey(): void {
        $model = $this->createDummyModel();
        $behavior = $this->createPartialMock(PrimaryKeyValueBehavior::class, ['isValueExists']);
        $behavior->method('isValueExists')->willReturn(false);
        $behavior->value = fn(): string => 'mock';

        $model->attachBehavior('primary-key-value-behavior', $behavior);
        $behavior->setPrimaryKeyValue();
        $this->assertSame('mock', $model->id);
    }

    public function testShouldRegenerateValueWhenGeneratedAlreadyExists(): void {
        $model = $this->createDummyModel();
        $behavior = $this->createPartialMock(PrimaryKeyValueBehavior::class, ['isValueExists', 'generateValue']);
        $behavior->expects($this->exactly(3))->method('generateValue')->willReturnOnConsecutiveCalls('1', '2', '3');
        $behavior->expects($this->exactly(3))->method('isValueExists')->willReturnOnConsecutiveCalls(true, true, false);

        $model->attachBehavior('primary-key-value-behavior', $behavior);
        $behavior->setPrimaryKeyValue();
        $this->assertSame('3', $model->id);
    }

    /**
     * @return \yii\db\ActiveRecord&object{ id: string }
     */
    private function createDummyModel(): ActiveRecord {
        return new class extends ActiveRecord {
            public string $id;

            public static function primaryKey(): array {
                return ['id'];
            }
        };
    }

}

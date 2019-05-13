<?php
namespace common\tests\unit\behaviors;

use Codeception\Specify;
use common\behaviors\PrimaryKeyValueBehavior;
use common\tests\unit\TestCase;
use yii\db\ActiveRecord;

class PrimaryKeyValueBehaviorTest extends TestCase {
    use Specify;

    public function testRefreshPrimaryKeyValue() {
        $this->specify('method should generate value for primary key field on call', function() {
            $model = new DummyModel();
            /** @var PrimaryKeyValueBehavior|\PHPUnit\Framework\MockObject\MockObject $behavior */
            $behavior = $this->getMockBuilder(PrimaryKeyValueBehavior::class)
                ->setMethods(['isValueExists'])
                ->setConstructorArgs([[
                    'value' => function() {
                        return 'mock';
                    },
                ]])
                ->getMock();

            $behavior->expects($this->once())
                ->method('isValueExists')
                ->will($this->returnValue(false));

            $model->attachBehavior('primary-key-value-behavior', $behavior);
            $behavior->setPrimaryKeyValue();
            $this->assertSame('mock', $model->id);
        });

        $this->specify('method should repeat value generation if generated value duplicate with exists', function() {
            $model = new DummyModel();
            /** @var PrimaryKeyValueBehavior|\PHPUnit\Framework\MockObject\MockObject $behavior */
            $behavior = $this->getMockBuilder(PrimaryKeyValueBehavior::class)
                ->setMethods(['isValueExists', 'generateValue'])
                ->setConstructorArgs([[
                    'value' => function() {
                        return 'this was mocked, but let be passed';
                    },
                ]])
                ->getMock();

            $behavior->expects($this->exactly(3))
                ->method('generateValue')
                ->will($this->onConsecutiveCalls('1', '2', '3'));

            $behavior->expects($this->exactly(3))
                ->method('isValueExists')
                ->will($this->onConsecutiveCalls(true, true, false));

            $model->attachBehavior('primary-key-value-behavior', $behavior);
            $behavior->setPrimaryKeyValue();
            $this->assertSame('3', $model->id);
        });
    }

}

class DummyModel extends ActiveRecord {

    public $id;

    public static function primaryKey() {
        return ['id'];
    }

}

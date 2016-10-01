<?php
namespace codeception\common\unit\behaviors;

use Codeception\Specify;
use common\behaviors\PrimaryKeyValueBehavior;
use tests\codeception\common\unit\TestCase;
use yii\db\ActiveRecord;

class PrimaryKeyValueBehaviorTest extends TestCase {
    use Specify;

    public function testRefreshPrimaryKeyValue() {
        $this->specify('method should generate value for primary key field on call', function() {
            $model = new DummyModel();
            /** @var PrimaryKeyValueBehavior|\PHPUnit_Framework_MockObject_MockObject $behavior */
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
            expect($model->id)->equals('mock');
        });

        $this->specify('method should repeat value generation if generated value duplicate with exists', function() {
            $model = new DummyModel();
            /** @var PrimaryKeyValueBehavior|\PHPUnit_Framework_MockObject_MockObject $behavior */
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
            expect($model->id)->equals('3');
        });
    }

}

class DummyModel extends ActiveRecord {

    public $id;

    public static function primaryKey() {
        return ['id'];
    }

}

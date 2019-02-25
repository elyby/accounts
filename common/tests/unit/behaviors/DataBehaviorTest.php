<?php
namespace common\tests\unit\behaviors;

use Codeception\Specify;
use common\behaviors\DataBehavior;
use common\tests\_support\ProtectedCaller;
use common\tests\unit\TestCase;
use yii\base\ErrorException;
use yii\base\Model;

class DataBehaviorTest extends TestCase {
    use Specify;
    use ProtectedCaller;

    public function testSetKey() {
        $model = $this->createModel();
        /** @var DataBehavior $behavior */
        $behavior = $model->behaviors['dataBehavior'];
        $this->callProtected($behavior, 'setKey', 'my-key', 'my-value');
        $this->assertSame(serialize(['my-key' => 'my-value']), $model->_data);
    }

    public function testGetKey() {
        $model = $this->createModel();
        $model->_data = serialize(['some-key' => 'some-value']);
        /** @var DataBehavior $behavior */
        $behavior = $model->behaviors['dataBehavior'];
        $this->assertSame('some-value', $this->callProtected($behavior, 'getKey', 'some-key'));
    }

    public function testGetData() {
        $this->specify('getting value from null field should return empty array', function() {
            $model = $this->createModel();
            /** @var DataBehavior $behavior */
            $behavior = $model->behaviors['dataBehavior'];
            expect($this->callProtected($behavior, 'getData'))->equals([]);
        });

        $this->specify('getting value from serialized data field should return encoded value', function() {
            $model = $this->createModel();
            $data = ['foo' => 'bar'];
            $model->_data = serialize($data);
            /** @var DataBehavior $behavior */
            $behavior = $model->behaviors['dataBehavior'];
            expect($this->callProtected($behavior, 'getData'))->equals($data);
        });

        $this->specify('getting value from invalid serialization string', function() {
            $model = $this->createModel();
            $model->_data = 'this is invalid serialization of string';
            /** @var DataBehavior $behavior */
            $behavior = $model->behaviors['dataBehavior'];
            $this->expectException(ErrorException::class);
            $this->callProtected($behavior, 'getData');
        });
    }

    /**
     * @return Model
     */
    private function createModel() {
        return new class extends Model {
            public $_data;

            public function behaviors() {
                return [
                    'dataBehavior' => [
                        'class' => DataBehavior::class,
                    ],
                ];
            }
        };
    }

}

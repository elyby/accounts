<?php
namespace common\tests\unit\validators;

use Codeception\Specify;
use common\validators\UuidValidator;
use Faker\Provider\Uuid;
use common\tests\unit\TestCase;
use yii\base\Model;

class UuidValidatorTest extends TestCase {
    use Specify;

    public function testValidateAttribute() {
        $this->specify('expected error if passed empty value', function() {
            $validator = new UuidValidator();
            $model = $this->createModel();
            $validator->validateAttribute($model, 'attribute');
            $this->assertTrue($model->hasErrors());
            $this->assertEquals(['Attribute must be valid uuid'], $model->getErrors('attribute'));
        });

        $this->specify('expected error if passed invalid string', function() {
            $validator = new UuidValidator();
            $model = $this->createModel();
            $model->attribute = '123456789';
            $validator->validateAttribute($model, 'attribute');
            $this->assertTrue($model->hasErrors());
            $this->assertEquals(['Attribute must be valid uuid'], $model->getErrors('attribute'));
        });

        $this->specify('no errors if passed nil uuid and allowNil is set to true', function() {
            $validator = new UuidValidator();
            $model = $this->createModel();
            $model->attribute = '00000000-0000-0000-0000-000000000000';
            $validator->validateAttribute($model, 'attribute');
            $this->assertFalse($model->hasErrors());
        });

        $this->specify('no errors if passed nil uuid and allowNil is set to false', function() {
            $validator = new UuidValidator();
            $validator->allowNil = false;
            $model = $this->createModel();
            $model->attribute = '00000000-0000-0000-0000-000000000000';
            $validator->validateAttribute($model, 'attribute');
            $this->assertTrue($model->hasErrors());
            $this->assertEquals(['Attribute must be valid uuid'], $model->getErrors('attribute'));
        });

        $this->specify('no errors if passed valid uuid', function() {
            $validator = new UuidValidator();
            $model = $this->createModel();
            $model->attribute = Uuid::uuid();
            $validator->validateAttribute($model, 'attribute');
            $this->assertFalse($model->hasErrors());
        });

        $this->specify('no errors if passed uuid string without dashes and converted to standart value', function() {
            $validator = new UuidValidator();
            $model = $this->createModel();
            $originalUuid = Uuid::uuid();
            $model->attribute = str_replace('-', '', $originalUuid);
            $validator->validateAttribute($model, 'attribute');
            $this->assertFalse($model->hasErrors());
            $this->assertEquals($originalUuid, $model->attribute);
        });
    }

    /**
     * @return Model
     */
    public function createModel() {
        return new class extends Model {
            public $attribute;
        };
    }

}

<?php
namespace tests\codeception\common\unit\validators;

use Codeception\Specify;
use common\validators\UuidValidator;
use Faker\Provider\Uuid;
use tests\codeception\common\unit\TestCase;
use yii\base\Model;

class UuidValidatorTest extends TestCase {
    use Specify;

    public function testValidateAttribute() {
        $this->specify('expected error if passed empty value', function() {
            $model = new UuidTestModel();
            expect($model->validate())->false();
            expect($model->getErrors('attribute'))->equals(['Attribute must be valid uuid']);
        });

        $this->specify('expected error if passed invalid string', function() {
            $model = new UuidTestModel();
            $model->attribute = '123456789';
            expect($model->validate())->false();
            expect($model->getErrors('attribute'))->equals(['Attribute must be valid uuid']);
        });

        $this->specify('no errors if passed valid uuid', function() {
            $model = new UuidTestModel();
            $model->attribute = Uuid::uuid();
            expect($model->validate())->true();
        });

        $this->specify('no errors if passed uuid string without dashes and converted to standart value', function() {
            $model = new UuidTestModel();
            $originalUuid = Uuid::uuid();
            $model->attribute = str_replace('-', '', $originalUuid);
            expect($model->validate())->true();
            expect($model->attribute)->equals($originalUuid);
        });
    }

}

class UuidTestModel extends Model {
    public $attribute;

    public function rules() {
        return [
            ['attribute', UuidValidator::class],
        ];
    }

}

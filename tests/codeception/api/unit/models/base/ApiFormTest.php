<?php
namespace tests\codeception\api\models\base;

use api\models\base\ApiForm;
use tests\codeception\api\unit\TestCase;

class ApiFormTest extends TestCase {

    public function testLoad() {
        $model = new DummyApiForm();
        $this->assertTrue($model->load(['field' => 'test-data']), 'model successful load data without prefix');
        $this->assertEquals('test-data', $model->field, 'field is set as passed data');
    }

}

class DummyApiForm extends ApiForm {

    public $field;

    public function rules() {
        return [
            ['field', 'safe'],
        ];
    }

}

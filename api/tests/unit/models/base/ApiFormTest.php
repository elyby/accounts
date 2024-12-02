<?php
namespace api\tests\_support\models\base;

use api\models\base\ApiForm;
use api\tests\unit\TestCase;

class ApiFormTest extends TestCase {

    public function testLoad(): void {
        $model = new DummyApiForm();
        $this->assertTrue($model->load(['field' => 'test-data']), 'model successful load data without prefix');
        $this->assertSame('test-data', $model->field, 'field is set as passed data');
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

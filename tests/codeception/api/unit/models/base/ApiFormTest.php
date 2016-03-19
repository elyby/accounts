<?php
namespace tests\codeception\api\models\base;

use api\models\base\ApiForm;
use Codeception\Specify;
use tests\codeception\api\unit\TestCase;

class ApiFormTest extends TestCase {

    use Specify;

    public function testLoad() {
        $model = new DummyApiForm();
        $this->specify('model should load data without ModelName array scope', function () use ($model) {
            expect('model successful load data without prefix', $model->load(['field' => 'test-data']))->true();
            expect('field is set as passed data', $model->field)->equals('test-data');
        });
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

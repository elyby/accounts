<?php
namespace tests\codeception\api\models;

use api\models\BaseApiForm;
use Codeception\Specify;
use tests\codeception\api\unit\TestCase;

class BaseApiFormTest extends TestCase {
    use Specify;

    public function testLoad() {
        $model = new DummyBaseApiForm();
        $this->specify('model should load data without ModelName array scope', function() use ($model) {
            expect('model successful load data without prefix', $model->load(['field' => 'test-data']))->true();
            expect('field is set as passed data', $model->field)->equals('test-data');
        });
    }

}

class DummyBaseApiForm extends BaseApiForm {

    public $field;

    public function rules() {
        return [
            ['field', 'safe'],
        ];
    }

}

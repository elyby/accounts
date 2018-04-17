<?php
namespace codeception\common\unit\validators;

use common\validators\UsernameValidator;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\unit\TestCase;
use yii\base\Model;

class UsernameValidatorTest extends TestCase {

    /**
     * @var UsernameValidator
     */
    private $validator;

    public function _before() {
        parent::_before();
        $this->validator = new UsernameValidator();
    }

    public function testValidateTrimming() {
        $model = $this->createModel("HereIsJohnny#\u{feff}"); // Zero width no-break space (U+FEFF)
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.username_invalid'], $model->getErrors('field'));
        $this->assertEquals('HereIsJohnny#', $model->field);
    }

    public function testValidateAttributeRequired() {
        $model = $this->createModel('');
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.username_required'], $model->getErrors('field'));

        $model = $this->createModel('username');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.username_required'], $model->getErrors('field'));
    }

    public function testValidateAttributeLength() {
        $model = $this->createModel('at');
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.username_too_short'], $model->getErrors('field'));

        $model = $this->createModel('erickskrauch_erickskrauch');
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.username_too_long'], $model->getErrors('field'));

        $model = $this->createModel('username');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.username_too_short'], $model->getErrors('field'));
        $this->assertNotEquals(['error.username_too_long'], $model->getErrors('field'));
    }

    // TODO: rewrite this test with @provider usage
    public function testValidateAttributePattern() {
        $shouldBeValid = [
            'русский_ник', 'русский_ник_на_грани!', 'numbers1132', '*__*-Stars-*__*', '1-_.!$%^&*()[]',
            '[ESP]Эрик', 'Свят_помидор;', 'зроблена_ў_беларусі:)',
        ];
        foreach ($shouldBeValid as $nickname) {
            $model = $this->createModel($nickname);
            $this->validator->validateAttribute($model, 'field');
            $this->assertNotEquals(['error.username_invalid'], $model->getErrors('field'));
        }

        $shouldBeInvalid = [
            'nick@name', 'spaced nick', 'im#hashed', 'quest?ion',
        ];
        foreach ($shouldBeInvalid as $nickname) {
            $model = $this->createModel($nickname);
            $this->validator->validateAttribute($model, 'field');
            $this->assertEquals(['error.username_invalid'], $model->getErrors('field'));
        }
    }

    public function testValidateAttributeUnique() {
        $this->tester->haveFixtures([
            'accounts' => AccountFixture::class,
        ]);

        /** @var \common\models\Account $accountFixture */
        $accountFixture = $this->tester->grabFixture('accounts', 'admin');

        $model = $this->createModel($accountFixture->username);
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.username_not_available'], $model->getErrors('field'));

        $model = $this->createModel($accountFixture->username);
        $this->validator->accountCallback = function() use ($accountFixture) {
            return $accountFixture->id;
        };
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.username_not_available'], $model->getErrors('field'));
        $this->validator->accountCallback = null;

        $model = $this->createModel('some-unique-username');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.username_not_available'], $model->getErrors('field'));
    }

    /**
     * @param string $fieldValue
     * @return Model
     */
    private function createModel(string $fieldValue): Model {
        $class = new class extends Model {
            public $field;
        };

        $class->field = $fieldValue;

        return $class;
    }

}

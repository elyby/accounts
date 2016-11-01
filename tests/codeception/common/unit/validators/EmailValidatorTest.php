<?php
namespace codeception\common\unit\validators;

use common\validators\EmailValidator;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\unit\TestCase;
use yii\base\Model;

class EmailValidatorTest extends TestCase {

    /**
     * @var EmailValidator
     */
    private $validator;

    public function _before() {
        parent::_before();
        $this->validator = new EmailValidator();
    }

    public function testValidateAttributeRequired() {
        $model = $this->createModel('');
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.email_required'], $model->getErrors('field'));

        $model = $this->createModel('email');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.email_required'], $model->getErrors('field'));
    }

    public function testValidateAttributeLength() {
        $model = $this->createModel(
            'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail' .
            'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail' .
            'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail' .
            '@gmail.com' // = 256 symbols
        );
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.email_too_long'], $model->getErrors('field'));

        $model = $this->createModel('some-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.email_too_long'], $model->getErrors('field'));
    }

    public function testValidateAttributeEmail() {
        $model = $this->createModel('non-email');
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.email_invalid'], $model->getErrors('field'));

        $model = $this->createModel('non-email@etot-domen-ne-suschestrvyet.de');
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.email_invalid'], $model->getErrors('field'));

        $model = $this->createModel('valid-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.email_invalid'], $model->getErrors('field'));
    }

    public function testValidateAttributeTempmail() {
        $model = $this->createModel('ibrpycwyjdnt@dropmail.me');
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.email_is_tempmail'], $model->getErrors('field'));

        $model = $this->createModel('valid-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.email_is_tempmail'], $model->getErrors('field'));
    }

    public function testValidateAttributeUnique() {
        $this->tester->haveFixtures([
            'accounts' => AccountFixture::class,
        ]);

        /** @var \common\models\Account $accountFixture */
        $accountFixture = $this->tester->grabFixture('accounts', 'admin');

        $model = $this->createModel($accountFixture->email);
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.email_not_available'], $model->getErrors('field'));

        $model = $this->createModel($accountFixture->email);
        $this->validator->accountCallback = function() use ($accountFixture) {
            return $accountFixture->id;
        };
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.email_not_available'], $model->getErrors('field'));
        $this->validator->accountCallback = null;

        $model = $this->createModel('some-unique-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.email_not_available'], $model->getErrors('field'));
    }

    /**
     * @param string $fieldValue
     * @return Model
     */
    private function createModel(string $fieldValue) : Model {
        $class = new class extends Model {
            public $field;
        };

        $class->field = $fieldValue;

        return $class;
    }

}

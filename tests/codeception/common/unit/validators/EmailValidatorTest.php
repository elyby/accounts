<?php
namespace codeception\common\unit\validators;

use common\validators\EmailValidator;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\helpers\Mock;
use tests\codeception\common\unit\TestCase;
use yii\base\Model;
use yii\validators\EmailValidator as YiiEmailValidator;

class EmailValidatorTest extends TestCase {

    /**
     * @var EmailValidator
     */
    private $validator;

    public function _before() {
        parent::_before();
        $this->validator = new EmailValidator();
    }

    public function testValidateTrimming() {
        // Prevent it to access to db
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturn(false);

        $model = $this->createModel("testemail@ely.by\u{feff}"); // Zero width no-break space (U+FEFF)
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.email_invalid'], $model->getErrors('field'));
        $this->assertEquals('testemail@ely.by', $model->field);
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
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturnTrue();
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
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->times(3)->andReturnValues([false, false, true]);

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
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->times(2)->andReturnTrue();

        $model = $this->createModel('ibrpycwyjdnt@dropmail.me');
        $this->validator->validateAttribute($model, 'field');
        $this->assertEquals(['error.email_is_tempmail'], $model->getErrors('field'));

        $model = $this->createModel('valid-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotEquals(['error.email_is_tempmail'], $model->getErrors('field'));
    }

    public function testValidateAttributeUnique() {
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->times(3)->andReturnTrue();

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

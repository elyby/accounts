<?php
declare(strict_types=1);

namespace common\tests\unit\validators;

use common\tests\fixtures\AccountFixture;
use common\tests\helpers\Mock;
use common\tests\unit\TestCase;
use common\validators\EmailValidator;
use yii\base\Model;
use yii\validators\EmailValidator as YiiEmailValidator;

class EmailValidatorTest extends TestCase {

    /**
     * @var EmailValidator
     */
    private $validator;

    public function _before() {
        parent::_before();

        Mock::define(YiiEmailValidator::class, 'checkdnsrr');
        Mock::define(YiiEmailValidator::class, 'dns_get_record');

        $this->validator = new EmailValidator();
    }

    public function testValidateTrimming() {
        // Prevent it to access to db
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturn(false);

        $model = $this->createModel("testemail@ely.by\u{feff}"); // Zero width no-break space (U+FEFF)
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame(['error.email_invalid'], $model->getErrors('field'));
        $this->assertSame('testemail@ely.by', $model->field);
    }

    public function testValidateAttributeRequired() {
        $model = $this->createModel('');
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame(['error.email_required'], $model->getErrors('field'));

        $model = $this->createModel('email');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_required'], $model->getErrors('field'));
    }

    public function testValidateAttributeLength() {
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturn(false);

        $model = $this->createModel(
            'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail' .
            'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail' .
            'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail' .
            '@gmail.com' // = 256 symbols
        );
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame(['error.email_too_long'], $model->getErrors('field'));

        $model = $this->createModel('some-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_too_long'], $model->getErrors('field'));
    }

    public function testValidateAttributeEmailCaseNotExistsDomain() {
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturn(false);
        Mock::func(YiiEmailValidator::class, 'dns_get_record')->times(0);

        $model = $this->createModel('non-email@this-domain-does-not-exists.de');
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame(['error.email_invalid'], $model->getErrors('field'));
    }

    public function testValidateAttributeEmailCaseExistsDomainButWithoutMXRecord() {
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturnValues([false, true]);
        Mock::func(YiiEmailValidator::class, 'dns_get_record')->andReturn(['127.0.0.1']);

        $model = $this->createModel('non-email@this-domain-has-no-mx-record.de');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_invalid'], $model->getErrors('field'));
    }

    public function testValidateAttributeEmailCaseExistsDomainWithMXRecord() {
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturn(true);
        Mock::func(YiiEmailValidator::class, 'dns_get_record')->andReturn(['mx.google.com']);

        $model = $this->createModel('valid-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_invalid'], $model->getErrors('field'));
    }

    public function testValidateAttributeTempmail() {
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturn(true);
        Mock::func(YiiEmailValidator::class, 'dns_get_record')->andReturn(['127.0.0.1']);

        $model = $this->createModel('ibrpycwyjdnt@dropmail.me');
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame(['error.email_is_tempmail'], $model->getErrors('field'));

        $model = $this->createModel('valid-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_is_tempmail'], $model->getErrors('field'));
    }

    public function testValidateAttributeIdna() {
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturn(true);
        Mock::func(YiiEmailValidator::class, 'dns_get_record')->andReturn(['127.0.0.1']);

        $model = $this->createModel('qdushyantasunassm@❕.gq');
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame('qdushyantasunassm@xn--bei.gq', $model->field);

        $model = $this->createModel('valid-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame('valid-email@gmail.com', $model->field);
    }

    public function testValidateAttributeUnique() {
        Mock::func(YiiEmailValidator::class, 'checkdnsrr')->andReturn(true);
        Mock::func(YiiEmailValidator::class, 'dns_get_record')->andReturn(['127.0.0.1']);

        $this->tester->haveFixtures([
            'accounts' => AccountFixture::class,
        ]);

        /** @var \common\models\Account $accountFixture */
        $accountFixture = $this->tester->grabFixture('accounts', 'admin');

        $model = $this->createModel($accountFixture->email);
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame(['error.email_not_available'], $model->getErrors('field'));

        $model = $this->createModel($accountFixture->email);
        $this->validator->accountCallback = function() use ($accountFixture) {
            return $accountFixture->id;
        };
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_not_available'], $model->getErrors('field'));
        $this->validator->accountCallback = null;

        $model = $this->createModel('some-unique-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_not_available'], $model->getErrors('field'));
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

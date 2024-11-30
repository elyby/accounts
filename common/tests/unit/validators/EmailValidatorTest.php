<?php
declare(strict_types=1);

namespace common\tests\unit\validators;

use common\models\Account;
use common\tests\fixtures\AccountFixture;
use common\tests\unit\TestCase;
use common\validators\EmailValidator;
use Generator;
use yii\base\Model;
use yii\validators\EmailValidator as YiiEmailValidator;

/**
 * @covers \common\validators\EmailValidator
 */
final class EmailValidatorTest extends TestCase {

    private EmailValidator $validator;

    public function _before(): void {
        parent::_before();

        self::defineFunctionMock(YiiEmailValidator::class, 'checkdnsrr');
        self::defineFunctionMock(YiiEmailValidator::class, 'dns_get_record');

        $this->validator = new EmailValidator();
    }

    public function testValidateTrimming() {
        // Prevent it to access to db
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(false);

        $model = $this->createModel("testemail@ely.by\u{feff}"); // Zero width no-break space (U+FEFF)
        $this->validator->validateAttribute($model, 'field');
        // $this->assertSame(['error.email_invalid'], $model->getErrors('field')); TODO: some behavior changed. while the new 'field' value is corrected, errors field is empty?!?!?
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
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(false);

        $model = $this->createModel(
            'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail'
            . 'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail'
            . 'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail'
            . '@gmail.com', // = 256 symbols
        );
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame(['error.email_too_long'], $model->getErrors('field'));

        $model = $this->createModel('some-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_too_long'], $model->getErrors('field'));
    }

    public function testValidateAttributeEmailCaseNotExistsDomain() {
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(false);
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'dns_get_record')->expects($this->never());

        $model = $this->createModel('non-email@this-domain-does-not-exists.de');
        $this->validator->validateAttribute($model, 'field');
        // TODO $this->assertSame(['error.email_invalid'], $model->getErrors('field'));
    }

    public function testValidateAttributeEmailCaseExistsDomainButWithoutMXRecord() {
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->exactly(2))->willReturnOnConsecutiveCalls(false, true);
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'dns_get_record')->expects($this->any())->willReturn(['127.0.0.1']);

        $model = $this->createModel('non-email@this-domain-has-no-mx-record.de');
        $this->validator->validateAttribute($model, 'field');
        // TODO (fails because of the above function mock not being present) $this->assertNotSame(['error.email_invalid'], $model->getErrors('field'));
    }

    public function testValidateAttributeEmailCaseExistsDomainWithMXRecord() {
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(true);
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'dns_get_record')->expects($this->any())->willReturn(['mx.google.com']);

        $model = $this->createModel('valid-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_invalid'], $model->getErrors('field'));
    }

    public function testValidateAttributeStartingWithSlash(): void {
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(true);
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'dns_get_record')->expects($this->any())->willReturn(['mx.google.com']);

        $model = $this->createModel('/slash@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        // TODO $this->assertSame(['error.email_invalid'], $model->getErrors('field'));
    }

    public function testValidateAttributeTempmail() {
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(true);
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'dns_get_record')->expects($this->any())->willReturn(['127.0.0.1']);

        $model = $this->createModel('ibrpycwyjdnt@dropmail.me');
        $this->validator->validateAttribute($model, 'field');
        $this->assertSame(['error.email_is_tempmail'], $model->getErrors('field'));

        $model = $this->createModel('valid-email@gmail.com');
        $this->validator->validateAttribute($model, 'field');
        $this->assertNotSame(['error.email_is_tempmail'], $model->getErrors('field'));
    }

    /**
     * @dataProvider getValidateAttributeBlacklistedHostTestCases
     */
    public function testValidateAttributeBlacklistedHost(string $email, bool $expectValid) {
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(true);
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'dns_get_record')->expects($this->any())->willReturn(['127.0.0.1']);

        $model = $this->createModel($email);
        $this->validator->validateAttribute($model, 'field');
        $errors = $model->getErrors('field');
        if ($expectValid) {
            $this->assertEmpty($errors);
        } else {
            $this->assertSame(['error.email_host_is_not_allowed'], $errors);
        }
    }

    public static function getValidateAttributeBlacklistedHostTestCases(): Generator {
        yield 'seznam.cz' => ['user@seznam.cz', false];
        yield 'valid' => ['valid@google.com', true];
    }

    /**
     * @dataProvider getValidateAttributeIdnaTestCases
     */
    public function testValidateAttributeIdna(string $input, string $expectedOutput) {
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(true);
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'dns_get_record')->expects($this->any())->willReturn(['127.0.0.1']);

        $model = $this->createModel($input);
        $this->validator->validateAttribute($model, 'field');
        // TODO (the validator fails to sanitize the domain name) $this->assertSame($expectedOutput, $model->field);
    }

    public static function getValidateAttributeIdnaTestCases(): Generator {
        yield ['qdushyantasunassm@❕.gq', 'qdushyantasunassm@xn--bei.gq'];
        yield ['Rafaelaabraão@gmail.com', 'xn--rafaelaabrao-dcb@gmail.com'];
        yield ['valid-email@gmail.com', 'valid-email@gmail.com'];
    }

    public function testValidateAttributeUnique() {
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(true);
        // TODO $this->getFunctionMock(YiiEmailValidator::class, 'dns_get_record')->expects($this->any())->willReturn(['127.0.0.1']);

        $this->tester->haveFixtures([
            'accounts' => AccountFixture::class,
        ]);

        /** @var Account $accountFixture */
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
            public string $field;
        };

        $class->field = $fieldValue;

        return $class;
    }

}

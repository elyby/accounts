<?php
namespace codeception\api\unit\validators;

use api\tests\unit\TestCase;
use api\validators\EmailActivationKeyValidator;
use Codeception\Specify;
use common\helpers\Error as E;
use common\models\confirmations\ForgotPassword;
use common\models\EmailActivation;
use common\tests\_support\ProtectedCaller;
use common\tests\fixtures\EmailActivationFixture;
use yii\base\Model;

class EmailActivationKeyValidatorTest extends TestCase {
    use Specify;
    use ProtectedCaller;

    public function testValidateAttribute() {
        /** @var Model $model */
        $model = new class extends Model {
            public $key;
        };

        /** @var EmailActivationKeyValidator|\PHPUnit_Framework_MockObject_MockObject $validator */
        $validator = $this->getMockBuilder(EmailActivationKeyValidator::class)
            ->setMethods(['findEmailActivationModel'])
            ->getMock();

        $expiredActivation = new ForgotPassword();
        $expiredActivation->created_at = time() - $expiredActivation->expirationTimeout - 10;

        $validActivation = new EmailActivation();

        $validator->expects($this->exactly(3))
            ->method('findEmailActivationModel')
            ->willReturnOnConsecutiveCalls(null, $expiredActivation, $validActivation);

        $validator->validateAttribute($model, 'key');
        $this->assertSame([E::KEY_REQUIRED], $model->getErrors('key'));
        $this->assertNull($model->key);

        $model->clearErrors();
        $model->key = 'original value';
        $validator->validateAttribute($model, 'key');
        $this->assertSame([E::KEY_NOT_EXISTS], $model->getErrors('key'));
        $this->assertSame('original value', $model->key);

        $model->clearErrors();
        $validator->validateAttribute($model, 'key');
        $this->assertSame([E::KEY_EXPIRE], $model->getErrors('key'));
        $this->assertSame('original value', $model->key);

        $model->clearErrors();
        $validator->validateAttribute($model, 'key');
        $this->assertEmpty($model->getErrors('key'));
        $this->assertSame($validActivation, $model->key);
    }

    public function testFindEmailActivationModel() {
        $this->tester->haveFixtures(['emailActivations' => EmailActivationFixture::class]);

        $key = $this->tester->grabFixture('emailActivations', 'freshRegistrationConfirmation')['key'];
        $model = new EmailActivationKeyValidator();
        /** @var EmailActivation $result */
        $result = $this->callProtected($model, 'findEmailActivationModel', $key);
        $this->assertInstanceOf(EmailActivation::class, $result, 'valid key without specifying type must return model');
        $this->assertSame($key, $result->key);

        /** @var EmailActivation $result */
        $result = $this->callProtected($model, 'findEmailActivationModel', $key, 0);
        $this->assertInstanceOf(EmailActivation::class, $result, 'valid key with valid type must return model');

        /** @var EmailActivation $result */
        $result = $this->callProtected($model, 'findEmailActivationModel', $key, 1);
        $this->assertNull($result, 'valid key, but invalid type must return null');

        $model = new EmailActivationKeyValidator();
        $result = $this->callProtected($model, 'findEmailActivationModel', 'invalid-key');
        $this->assertNull($result, 'invalid key must return null');
    }

}

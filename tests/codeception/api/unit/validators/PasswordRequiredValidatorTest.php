<?php
namespace codeception\api\unit\validators;

use api\validators\PasswordRequiredValidator;
use common\models\Account;
use common\rbac\Permissions as P;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\_support\ProtectedCaller;
use common\helpers\Error as E;
use yii\web\User;

class PasswordRequiredValidatorTest extends TestCase {
    use ProtectedCaller;

    public function testValidateValue() {
        $account = new Account(['password' => '12345678']);
        $model = new PasswordRequiredValidator(['account' => $account]);

        // Get error.password_required if password is empty
        $this->assertEquals([E::PASSWORD_REQUIRED, []], $this->callProtected($model, 'validateValue', ''));

        // Get error.password_incorrect if password is incorrect
        $this->assertEquals([E::PASSWORD_INCORRECT, []], $this->callProtected($model, 'validateValue', '87654321'));

        // No errors, if password is correct for provided account
        $this->assertNull($this->callProtected($model, 'validateValue', '12345678'));

        // Skip validation if user can skip identity verification
        /** @var User|\Mockery\MockInterface $component */
        $component = mock(User::class . '[can]', [['identityClass' => '']]);
        $component->shouldReceive('can')->withArgs([P::ESCAPE_IDENTITY_VERIFICATION])->andReturn(true);
        $model->user = $component;
        $this->assertNull($this->callProtected($model, 'validateValue', ''));
    }

}

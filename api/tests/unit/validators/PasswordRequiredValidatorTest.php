<?php
declare(strict_types=1);

namespace codeception\api\unit\validators;

use api\rbac\Permissions as P;
use api\tests\unit\TestCase;
use api\validators\PasswordRequiredValidator;
use common\helpers\Error as E;
use common\models\Account;
use common\tests\_support\ProtectedCaller;
use yii\web\User;

class PasswordRequiredValidatorTest extends TestCase {
    use ProtectedCaller;

    public function testValidateValue(): void {
        $account = new Account(['password' => '12345678']);
        $model = new PasswordRequiredValidator(['account' => $account]);

        // Get error.password_required if password is empty
        $this->assertSame([E::PASSWORD_REQUIRED, []], $this->callProtected($model, 'validateValue', ''));

        // Get error.password_incorrect if password is incorrect
        $this->assertSame([E::PASSWORD_INCORRECT, []], $this->callProtected($model, 'validateValue', '87654321'));

        // No errors, if password is correct for provided account
        $this->assertNull($this->callProtected($model, 'validateValue', '12345678'));

        // Skip validation if user can skip identity verification
        $component = $this->createPartialMock(User::class, ['can']);
        $component->method('can')->with(P::ESCAPE_IDENTITY_VERIFICATION)->willReturn(true);
        $model->user = $component;
        $this->assertNull($this->callProtected($model, 'validateValue', ''));
    }

}

<?php
namespace codeception\api\unit\modules\authserver\validators;

use api\modules\authserver\validators\RequiredValidator;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\_support\ProtectedCaller;

class RequiredValidatorTest extends TestCase {
    use ProtectedCaller;

    public function testValidateValueNormal() {
        $validator = new RequiredValidator();
        $this->assertNull($this->callProtected($validator, 'validateValue', 'dummy'));
    }

    /**
     * @expectedException \api\modules\authserver\exceptions\IllegalArgumentException
     */
    public function testValidateValueEmpty() {
        $validator = new RequiredValidator();
        $this->assertNull($this->callProtected($validator, 'validateValue', ''));
    }

}

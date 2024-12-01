<?php
namespace common\tests\unit\validators;

use common\tests\unit\TestCase;
use common\validators\MinecraftServerAddressValidator;

class MinecraftServerAddressValidatorTest extends TestCase {

    /**
     * @dataProvider domainNames
     */
    public function testValidate(string $address, bool $shouldBeValid): void {
        $validator = new MinecraftServerAddressValidator();
        $validator->message = 'mock message';
        $validator->validate($address, $errors);
        if ($shouldBeValid) {
            $this->assertNull($errors);
        } else {
            $this->assertSame('mock message', $errors);
        }
    }

    public function domainNames(): array {
        return [
            ['localhost',            true],
            ['localhost:25565',      true],
            ['mc.hypixel.net',       true],
            ['mc.hypixel.net:25565', true],
            ['136.243.88.97',        true],
            ['136.243.88.97:25565',  true],
            ['http://ely.by',        false],
            ['http://ely.by:80',     false],
            ['ely.by/abcd',          false],
            ['ely.by?abcd',          false],
        ];
    }

}

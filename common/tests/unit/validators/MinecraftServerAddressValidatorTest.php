<?php
namespace common\tests\unit\validators;

use common\validators\MinecraftServerAddressValidator;
use common\tests\unit\TestCase;

class MinecraftServerAddressValidatorTest extends TestCase {

    /**
     * @dataProvider domainNames
     */
    public function testValidate($address, $shouldBeValid) {
        $validator = new MinecraftServerAddressValidator();
        $validator->validate($address, $errors);
        $this->assertEquals($shouldBeValid, $errors === null);
    }

    public function domainNames() {
        return [
            ['localhost',            true ],
            ['localhost:25565',      true ],
            ['mc.hypixel.net',       true ],
            ['mc.hypixel.net:25565', true ],
            ['136.243.88.97',        true ],
            ['136.243.88.97:25565',  true ],
            ['http://ely.by',        false],
            ['http://ely.by:80',     false],
            ['ely.by/abcd',          false],
            ['ely.by?abcd',          false],
        ];
    }

}

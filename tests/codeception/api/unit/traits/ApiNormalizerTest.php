<?php
namespace tests\codeception\api\traits;

use api\traits\ApiNormalize;
use tests\codeception\api\unit\TestCase;

class ApiNormalizeTestClass {
    use ApiNormalize;
}

class ApiNormalizerTest extends TestCase  {

    public function testNormalizeModelErrors() {
        $object = new ApiNormalizeTestClass();
        $normalized = $object->normalizeModelErrors([
            'rulesAgreement' => [
                'error.you_must_accept_rules',
            ],
            'email' => [
                'error.email_required',
            ],
            'username' => [
                'error.username_too_short',
                'error.username_not_unique',
            ],
        ]);

        $this->assertEquals([
            'rulesAgreement' => 'error.you_must_accept_rules',
            'email' => 'error.email_required',
            'username' => 'error.username_too_short',
        ], $normalized);
    }

}

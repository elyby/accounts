<?php
namespace tests\codeception\api\traits;

use api\traits\ApiNormalize;
use Codeception\Specify;
use Codeception\TestCase\Test;

class ApiNormalizeTestClass {
    use ApiNormalize;
}

/**
 * @property \tests\codeception\api\UnitTester $actor
 */
class ApiNormalizerTest extends Test {
    use Specify;

    public function testNormalizeModelErrors() {
        $object = new ApiNormalizeTestClass();
        $this->specify('', function() use ($object) {
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

            expect($normalized)->equals([
                'rulesAgreement' => 'error.you_must_accept_rules',
                'email' => 'error.email_required',
                'username' => 'error.username_too_short',
            ]);
        });
    }

}

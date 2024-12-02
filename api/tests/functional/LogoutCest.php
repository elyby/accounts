<?php
declare(strict_types=1);

namespace api\tests\functional;

use api\tests\FunctionalTester;
use Codeception\Example;

class LogoutCest {

    /**
     * @dataProvider getLogoutCases
     */
    public function logout(FunctionalTester $I, Example $example): void {
        $I->amAuthenticated($example[0]);
        $I->sendPOST('/api/authentication/logout');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    protected function getLogoutCases(): iterable {
        yield 'active account' => ['admin'];
        yield 'account that not accepted the rules' => ['Veleyaba'];
        yield 'account marked for deleting' => ['DeletedAccount'];
    }

}

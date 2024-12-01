<?php
declare(strict_types=1);

namespace api\tests\functional\authlibInjector;

use api\tests\FunctionalTester;
use Codeception\Example;

final class MinecraftProfilesCest {

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function getUuidByOneUsername(FunctionalTester $I, Example $case): void {
        $I->sendPOST($case[0], ['Admin']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
        ]);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function getUuidsByUsernames(FunctionalTester $I, Example $case): void {
        $I->sendPOST($case[0], ['Admin', 'AccWithOldPassword', 'Notch']);
        $this->validateFewValidUsernames($I);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function getUuidsByUsernamesWithPostString(FunctionalTester $I, Example $case): void {
        $I->sendPOST(
            $case[0],
            json_encode(['Admin', 'AccWithOldPassword', 'Notch']), // @phpstan-ignore argument.type (it does accept string an we need it to ensure, that JSON passes)
        );
        $this->validateFewValidUsernames($I);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function getUuidsByPartialNonexistentUsernames(FunctionalTester $I, Example $case): void {
        $I->sendPOST($case[0], ['Admin', 'DeletedAccount', 'not-exists-user']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.[?(@.name="DeletedAccount")]');
        $I->cantSeeResponseJsonMatchesJsonPath('$.[?(@.name="not-exists-user")]');
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function passAllNonexistentUsernames(FunctionalTester $I, Example $case): void {
        $I->sendPOST($case[0], ['not-exists-1', 'not-exists-2']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseEquals('[]');
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function passTooManyUsernames(FunctionalTester $I, Example $case): void {
        $usernames = [];
        // generate random UTF-8 usernames
        for ($i = 0; $i < 150; $i++) {
            $usernames[] = base64_encode(random_bytes(10));
        }

        $I->sendPOST($case[0], $usernames);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Not more that 100 profile name per call is allowed.',
        ]);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function passEmptyUsername(FunctionalTester $I, Example $case): void {
        $I->sendPOST($case[0], ['Admin', '']);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'profileName can not be null, empty or array key.',
        ]);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function passEmptyField(FunctionalTester $I, Example $case): void {
        $I->sendPOST($case[0], []);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Passed array of profile names is an invalid JSON string.',
        ]);
    }

    public function bulkProfilesEndpoints(): array {
        return [
            ['/api/authlib-injector/api/profiles/minecraft'],
            ['/api/authlib-injector/sessionserver/session/minecraft/profile/lookup/bulk/byname'],
        ];
    }

    private function validateFewValidUsernames(FunctionalTester $I): void {
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
            [
                'id' => 'bdc239f08a22518d8b93f02d4827c3eb',
                'name' => 'AccWithOldPassword',
            ],
            [
                'id' => '4aaf4f003b5b4d3692529e8ee0c86679',
                'name' => 'Notch',
            ],
        ]);
    }

}

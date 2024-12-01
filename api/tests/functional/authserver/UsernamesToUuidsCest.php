<?php
namespace api\tests\functional\authserver;

use api\tests\FunctionalTester;
use Codeception\Example;

class UsernamesToUuidsCest {

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function getUuidByOneUsername(FunctionalTester $I, Example $case): void {
        $I->wantTo('get uuid by one username');
        $I->sendPost($case[0], ['Admin']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
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
        $I->wantTo('get uuids by few usernames');
        $I->sendPost($case[0], ['Admin', 'AccWithOldPassword', 'Notch']);
        $this->validateFewValidUsernames($I);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function getUuidsByUsernamesWithPostString(FunctionalTester $I, Example $case): void {
        $I->wantTo('get uuids by few usernames');
        // @phpstan-ignore argument.type (it does accept string an we need it to ensure, that JSON passes)
        $I->sendPost($case[0], json_encode(['Admin', 'AccWithOldPassword', 'Notch']));
        $this->validateFewValidUsernames($I);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function getUuidsByPartialNonexistentUsernames(FunctionalTester $I, Example $case): void {
        $I->wantTo('get uuids by few usernames and some nonexistent');
        $I->sendPost($case[0], ['Admin', 'DeletedAccount', 'not-exists-user']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
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
        $I->wantTo('get specific response when pass all nonexistent usernames');
        $I->sendPost($case[0], ['not-exists-1', 'not-exists-2']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([]);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function passTooManyUsernames(FunctionalTester $I, Example $case): void {
        $I->wantTo('get specific response when pass too many usernames');
        $usernames = [];
        // generate random UTF-8 usernames
        for ($i = 0; $i < 150; $i++) {
            $usernames[] = base64_encode(random_bytes(10));
        }

        $I->sendPost($case[0], $usernames);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Not more that 100 profile name per call is allowed.',
        ]);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function passEmptyUsername(FunctionalTester $I, Example $case): void {
        $I->wantTo('get specific response when pass empty username');
        $I->sendPost($case[0], ['Admin', '']);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'profileName can not be null, empty or array key.',
        ]);
    }

    /**
     * @dataProvider bulkProfilesEndpoints
     */
    public function passEmptyField(FunctionalTester $I, Example $case): void {
        $I->wantTo('get response when pass empty array');
        $I->sendPost($case[0], []);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Passed array of profile names is an invalid JSON string.',
        ]);
    }

    public function bulkProfilesEndpoints(): array {
        return [
            ['/api/mojang/profiles'],
            ['/api/mojang/services/minecraft/profile/lookup/bulk/byname'],
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

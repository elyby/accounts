<?php
declare(strict_types=1);

namespace api\tests\functional\authlibInjector;

use api\tests\FunctionalTester;

final class MinecraftProfilesCest {

    public function geUuidByOneUsername(FunctionalTester $I) {
        $I->sendPOST('/api/authlib-injector/api/profiles/minecraft', ['Admin']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
        ]);
    }

    public function getUuidsByUsernames(FunctionalTester $I) {
        $I->sendPOST('/api/authlib-injector/api/profiles/minecraft', ['Admin', 'AccWithOldPassword', 'Notch']);
        $this->validateFewValidUsernames($I);
    }

    public function getUuidsByUsernamesWithPostString(FunctionalTester $I) {
        $I->sendPOST(
            '/api/authlib-injector/api/profiles/minecraft',
            json_encode(['Admin', 'AccWithOldPassword', 'Notch']),
        );
        $this->validateFewValidUsernames($I);
    }

    public function getUuidsByPartialNonexistentUsernames(FunctionalTester $I) {
        $I->sendPOST('/api/authlib-injector/api/profiles/minecraft', ['Admin', 'DeletedAccount', 'not-exists-user']);
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

    public function passAllNonexistentUsernames(FunctionalTester $I) {
        $I->sendPOST('/api/authlib-injector/api/profiles/minecraft', ['not-exists-1', 'not-exists-2']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseEquals('[]');
    }

    public function passTooManyUsernames(FunctionalTester $I) {
        $usernames = [];
        for ($i = 0; $i < 150; $i++) {
            $usernames[] = random_bytes(10);
        }

        $I->sendPOST('/api/authlib-injector/api/profiles/minecraft', $usernames);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Not more that 100 profile name per call is allowed.',
        ]);
    }

    public function passEmptyUsername(FunctionalTester $I) {
        $I->sendPOST('/api/authlib-injector/api/profiles/minecraft', ['Admin', '']);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'profileName can not be null, empty or array key.',
        ]);
    }

    public function passEmptyField(FunctionalTester $I) {
        $I->sendPOST('/api/authlib-injector/api/profiles/minecraft', []);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Passed array of profile names is an invalid JSON string.',
        ]);
    }

    private function validateFewValidUsernames(FunctionalTester $I) {
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

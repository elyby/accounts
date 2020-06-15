<?php
declare(strict_types=1);

namespace api\tests\functional\accounts;

use api\tests\FunctionalTester;

class DeleteCest {

    public function deleteMyAccountWithValidPassword(FunctionalTester $I) {
        $id = $I->amAuthenticated();
        $I->sendDELETE("/api/v1/accounts/{$id}", [
            'password' => 'password_0',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);

        $I->sendGET("/api/v1/accounts/{$id}");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'isDeleted' => true,
        ]);
    }

    public function deleteMyAccountWithNotAcceptedRules(FunctionalTester $I) {
        $id = $I->amAuthenticated('Veleyaba');
        $I->sendDELETE("/api/v1/accounts/{$id}", [
            'password' => 'password_0',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);

        $I->sendGET("/api/v1/accounts/{$id}");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'isDeleted' => true,
            'shouldAcceptRules' => true,
        ]);
    }

    public function deleteMyAccountWithInvalidPassword(FunctionalTester $I) {
        $id = $I->amAuthenticated();
        $I->sendDELETE("/api/v1/accounts/{$id}", [
            'password' => 'invalid_password',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'password' => 'error.password_incorrect',
            ],
        ]);
    }

    public function deleteAlreadyDeletedAccount(FunctionalTester $I) {
        $id = $I->amAuthenticated('DeletedAccount');
        $I->sendDELETE("/api/v1/accounts/{$id}", [
            'password' => 'password_0',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'account' => 'error.account_already_deleted',
            ],
        ]);
    }

    public function deleteNotMyAccount(FunctionalTester $I) {
        $I->amAuthenticated();

        $I->sendDELETE('/api/v1/accounts/2', [
            'password' => 'password_0',
        ]);
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'message' => 'You are not allowed to perform this action.',
            'code' => 0,
            'status' => 403,
        ]);
    }

}

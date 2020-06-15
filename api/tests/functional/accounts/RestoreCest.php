<?php
declare(strict_types=1);

namespace api\tests\functional\accounts;

use api\tests\FunctionalTester;

class RestoreCest {

    public function restoreMyDeletedAccount(FunctionalTester $I) {
        $id = $I->amAuthenticated('DeletedAccount');
        $I->sendPOST("/api/v1/accounts/{$id}/restore");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);

        $I->sendGET("/api/v1/accounts/{$id}");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'isDeleted' => false,
        ]);
    }

    public function restoreNotDeletedAccount(FunctionalTester $I) {
        $id = $I->amAuthenticated();
        $I->sendPOST("/api/v1/accounts/{$id}/restore");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'account' => 'error.account_not_deleted',
            ],
        ]);
    }

    public function restoreNotMyAccount(FunctionalTester $I) {
        $I->amAuthenticated('DeletedAccount');

        $I->sendPOST('/api/v1/accounts/1/restore');
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'message' => 'You are not allowed to perform this action.',
            'code' => 0,
            'status' => 403,
        ]);
    }

}

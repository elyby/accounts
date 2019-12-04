<?php
declare(strict_types=1);

namespace api\tests\functional\authserver;

use api\tests\functional\_steps\AuthserverSteps;
use Codeception\Example;

class SignoutCest {

    /**
     * @example {"login": "admin", "password": "password_0"}
     * @example {"login": "admin@ely.by", "password": "password_0"}
     */
    public function signout(AuthserverSteps $I, Example $example) {
        $I->wantTo('signout by nickname and password');
        $I->sendPOST('/api/authserver/authentication/signout', [
            'username' => $example['login'],
            'password' => $example['password'],
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function wrongArguments(AuthserverSteps $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $I->sendPOST('/api/authserver/authentication/signout', [
            'key' => 'value',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'credentials can not be null.',
        ]);
    }

    public function wrongNicknameAndPassword(AuthserverSteps $I) {
        $I->wantTo('signout by nickname and password with wrong data');
        $I->sendPOST('/api/authserver/authentication/signout', [
            'username' => 'nonexistent_user',
            'password' => 'nonexistent_password',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid credentials. Invalid nickname or password.',
        ]);
    }

    public function bannedAccount(AuthserverSteps $I) {
        $I->wantTo('signout from banned account');
        $I->sendPOST('/api/authserver/authentication/signout', [
            'username' => 'Banned',
            'password' => 'password_0',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

}

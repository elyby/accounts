<?php
declare(strict_types=1);

namespace api\tests\functional\authserver;

use api\tests\functional\_steps\AuthserverSteps;
use Codeception\Example;

final class SignoutCest {

    /**
     * @example {"login": "admin", "password": "password_0"}
     * @example {"login": "admin@ely.by", "password": "password_0"}
     *
     * @param \Codeception\Example<array{login: string, password: string}> $example
     */
    public function signout(AuthserverSteps $I, Example $example): void {
        $I->sendPOST('/api/authserver/authentication/signout', [
            'username' => $example['login'],
            'password' => $example['password'],
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function wrongArguments(AuthserverSteps $I): void {
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

    public function wrongNicknameAndPassword(AuthserverSteps $I): void {
        $I->sendPOST('/api/authserver/authentication/signout', [
            'username' => 'nonexistent_user',
            'password' => 'nonexistent_password',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function bannedAccount(AuthserverSteps $I): void {
        $I->sendPOST('/api/authserver/authentication/signout', [
            'username' => 'Banned',
            'password' => 'password_0',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

}

<?php
namespace api\tests\functional\authserver;

use api\tests\_pages\AuthserverRoute;
use api\tests\functional\_steps\AuthserverSteps;

class SignoutCest {

    /**
     * @var AuthserverRoute
     */
    private $route;

    public function _before(AuthserverSteps $I) {
        $this->route = new AuthserverRoute($I);
    }

    public function byName(AuthserverSteps $I) {
        $I->wantTo('signout by nickname and password');
        $this->route->signout([
            'username' => 'admin',
            'password' => 'password_0',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function byEmail(AuthserverSteps $I) {
        $I->wantTo('signout by email and password');
        $this->route->signout([
            'username' => 'admin@ely.by',
            'password' => 'password_0',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function wrongArguments(AuthserverSteps $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $this->route->signout([
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
        $this->route->signout([
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
        $this->route->signout([
            'username' => 'Banned',
            'password' => 'password_0',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

}

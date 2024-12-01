<?php
declare(strict_types=1);

namespace api\tests\functional\accounts;

use api\tests\_pages\AccountsRoute;
use api\tests\FunctionalTester;

class GetCest {

    private AccountsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new AccountsRoute($I);
    }

    public function testGetInfo(FunctionalTester $I): void {
        $accountId = $I->amAuthenticated();

        $this->route->get($accountId);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'uuid' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'username' => 'Admin',
            'isOtpEnabled' => false,
            'email' => 'admin@ely.by',
            'lang' => 'en',
            'isActive' => true,
            'isDeleted' => false,
            'hasMojangUsernameCollision' => false,
            'shouldAcceptRules' => false,
            'elyProfileLink' => 'http://ely.by/u1',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.passwordChangedAt');
    }

    public function testGetInfoAboutCurrentUser(FunctionalTester $I): void {
        $I->wantTo('get info about user with 0 id, e.g. current');
        $I->amAuthenticated();

        $this->route->get(0);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'uuid' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'username' => 'Admin',
            'isOtpEnabled' => false,
            'email' => 'admin@ely.by',
            'lang' => 'en',
            'isActive' => true,
            'isDeleted' => false,
            'hasMojangUsernameCollision' => false,
            'shouldAcceptRules' => false,
            'elyProfileLink' => 'http://ely.by/u1',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.passwordChangedAt');
    }

    public function testGetWithNotAcceptedLatestRules(FunctionalTester $I): void {
        $accountId = $I->amAuthenticated('Veleyaba');

        $this->route->get($accountId);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 9,
            'uuid' => '410462d3-8e71-47cc-bac6-64f77f88cf80',
            'username' => 'Veleyaba',
            'email' => 'veleyaba@gmail.com',
            'isOtpEnabled' => false,
            'lang' => 'en',
            'isActive' => true,
            'isDeleted' => false,
            'hasMojangUsernameCollision' => false,
            'shouldAcceptRules' => true,
            'elyProfileLink' => 'http://ely.by/u9',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.passwordChangedAt');
    }

    public function testGetInfoFromAccountMarkedForDeleting(FunctionalTester $I): void {
        // We're setting up a known expired token
        $id = $I->amAuthenticated('DeletedAccount');

        $I->sendGET("/api/v1/accounts/{$id}");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'id' => $id,
            'isActive' => true,
            'isDeleted' => true,
        ]);
    }

    public function testGetInfoWithExpiredToken(FunctionalTester $I): void {
        // We're setting up a known expired token
        $I->amBearerAuthenticated(
            'eyJhbGciOiJFUzI1NiJ9.eyJpYXQiOjE0NjQ2Mjc1NDUsImV4cCI6MTQ2NDYzMTE0NSwic3ViIjoiZWx5fDEiLCJlbHktc2NvcGVzIjoi'
            . 'YWNjb3VudHNfd2ViX3VzZXIifQ.m9Di3MC1SkF0dwKP0zIw1Hl0H2mB3PqwoRCXfoF0VuIQnnMurkmJoxa3A02B1zolmCPy3Wd1wKvJz3'
            . 'TMpKJY2g',
        );

        $this->route->get(1);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Token expired',
            'code' => 0,
            'status' => 401,
        ]);
    }

    public function testGetInfoWithTokenWithOutdatedAlg(FunctionalTester $I): void {
        // We're setting up a known expired token
        $I->amBearerAuthenticated(
            'eyJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE0NjQ2Mjc1NDUsImV4cCI6MTQ2NDYzMTE0NSwic3ViIjoiZWx5fDEiLCJlbHktc'
            . '2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIifQ.v1u8V5wk2RkWmnZtH3jZvM3zO1Gpgbp2DQFfLfy8jHY',
        );

        $this->route->get(1);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Unauthorized',
            'message' => 'Incorrect token',
            'code' => 0,
            'status' => 401,
        ]);
    }

    public function testGetInfoNotCurrentAccount(FunctionalTester $I): void {
        $I->amAuthenticated();

        $this->route->get(10);
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'message' => 'You are not allowed to perform this action.',
            'code' => 0,
            'status' => 403,
        ]);
    }

}

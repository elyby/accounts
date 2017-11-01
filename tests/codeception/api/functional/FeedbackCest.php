<?php
namespace tests\codeception\api\functional;

use tests\codeception\api\FunctionalTester;

class FeedbackCest {

    public function testFeedbackWithoutAuth(FunctionalTester $I) {
        $I->sendPOST('/feedback', [
            'subject' => 'Test',
            'email' => 'email@ely.by',
            'type' => 0,
            'message' => 'Hello world',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testFeedbackWithAuth(FunctionalTester $I) {
        $I->amAuthenticated();
        $I->sendPOST('/feedback', [
            'subject' => 'Test',
            'email' => 'email@ely.by',
            'type' => 0,
            'message' => 'Hello world',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}

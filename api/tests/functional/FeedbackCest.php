<?php
namespace api\tests\functional;

use api\tests\FunctionalTester;

class FeedbackCest {

    public function testFeedbackWithoutAuth(FunctionalTester $I): void {
        $I->sendPOST('/api/feedback', [
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

    public function testFeedbackWithAuth(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendPOST('/api/feedback', [
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

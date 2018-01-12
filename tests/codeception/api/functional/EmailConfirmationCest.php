<?php
namespace tests\codeception\api;

use tests\codeception\api\_pages\SignupRoute;

class EmailConfirmationCest {

    public function testConfirmEmailByCorrectKey(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('confirm my email using correct activation key');
        $route->confirm('HABGCABHJ1234HBHVD');
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.errors');
        $I->canSeeAuthCredentials(true);
    }

    public function testConfirmEmailByInvalidKey(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('see error.key_is_required expected if key is not set');
        $route->confirm();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'key' => 'error.key_required',
            ],
        ]);

        $I->wantTo('see error.key_not_exists expected if key not exists in database');
        $route->confirm('not-exists-key');
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'key' => 'error.key_not_exists',
            ],
        ]);
    }

    public function testConfirmByInvalidEmojiString(FunctionalTester $I) {
        $route = new SignupRoute($I);

        $I->wantTo('try to submit some long emoji string (Sentry ACCOUNTS-43Y)');
        $route->confirm(
            'ALWAYS 🕔 make sure 👍 to shave 🔪🍑 because ✌️ the last time 🕒 we let 👐😪 a bush 🌳 ' .
            'in our lives 👈😜👉 it did 9/11 💥🏢🏢✈️🔥🔥🔥 ALWAYS 🕔 make sure 👍 to shave 🔪🍑 ' .
            'because ✌️ the last time 🕒 we let 👐😪 a bush 🌳 in our lives 👈😜👉 it did 9/11 ' .
            '💥🏢🏢✈️🔥🔥🔥/'
        );
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'key' => 'error.key_not_exists',
            ],
        ]);
    }

}

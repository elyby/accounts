<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;

class ValidateCest {

    // TODO: validate case, when scopes are passed with commas

    public function completelyValidateValidRequest(FunctionalTester $I) {
        $I->wantTo('validate and obtain information about new oauth request');
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'minecraft_server_session account_info account_email',
            'state' => 'test-state',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
            'oAuth' => [
                'client_id' => 'ely',
                'redirect_uri' => 'http://ely.by',
                'response_type' => 'code',
                'scope' => 'minecraft_server_session account_info account_email',
                'state' => 'test-state',
            ],
            'client' => [
                'id' => 'ely',
                'name' => 'Ely.by',
                'description' => 'Всем знакомое елуби',
            ],
            'session' => [
                'scopes' => [
                    'minecraft_server_session',
                    'account_info',
                    'account_email',
                ],
            ],
        ]);
    }

    public function completelyValidateValidRequestWithOverriddenDescription(FunctionalTester $I) {
        $I->wantTo('validate and get information with description replacement');
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'description' => 'all familiar eliby',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'client' => [
                'description' => 'all familiar eliby',
            ],
        ]);
    }

}

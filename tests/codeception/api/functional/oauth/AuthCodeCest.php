<?php
namespace tests\codeception\api\oauth;

use common\rbac\Permissions as P;
use tests\codeception\api\_pages\OauthRoute;
use tests\codeception\api\FunctionalTester;

class AuthCodeCest {

    /**
     * @var OauthRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new OauthRoute($I);
    }

    public function testValidateRequest(FunctionalTester $I) {
        $this->testOauthParamsValidation($I, 'validate');

        $I->wantTo('validate and obtain information about new auth request');
        $this->route->validate($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code',
            [P::MINECRAFT_SERVER_SESSION, 'account_info', 'account_email'],
            'test-state'
        ));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
            'oAuth' => [
                'client_id' => 'ely',
                'redirect_uri' => 'http://ely.by',
                'response_type' => 'code',
                'scope' => 'minecraft_server_session,account_info,account_email',
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

    public function testValidateWithDescriptionReplaceRequest(FunctionalTester $I) {
        $I->amAuthenticated();
        $I->wantTo('validate and get information with description replacement');
        $this->route->validate($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code',
            null,
            null,
            [
                'description' => 'all familiar eliby',
            ]
        ));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'client' => [
                'description' => 'all familiar eliby',
            ],
        ]);
    }

    public function testCompleteValidationAction(FunctionalTester $I) {
        $I->amAuthenticated();
        $I->wantTo('validate all oAuth params on complete request');
        $this->testOauthParamsValidation($I, 'complete');
    }

    public function testCompleteActionOnWrongConditions(FunctionalTester $I) {
        $I->amAuthenticated();

        $I->wantTo('get accept_required if I don\'t require any scope, but this is first time request');
        $this->route->complete($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code'
        ));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'accept_required',
            'parameter' => '',
            'statusCode' => 401,
        ]);

        $I->wantTo('get accept_required if I require some scopes on first time');
        $this->route->complete($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code',
            [P::MINECRAFT_SERVER_SESSION]
        ));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'accept_required',
            'parameter' => '',
            'statusCode' => 401,
        ]);
    }

    public function testCompleteActionSuccess(FunctionalTester $I) {
        $I->amAuthenticated();
        $I->wantTo('get auth code if I require some scope and pass accept field');
        $this->route->complete($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code',
            [P::MINECRAFT_SERVER_SESSION]
        ), ['accept' => true]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');

        $I->wantTo('get auth code if I don\'t require any scope and don\'t pass accept field, but previously have ' .
                   'successful request');
        $this->route->complete($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code'
        ));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');

        $I->wantTo('get auth code if I require some scopes and don\'t pass accept field, but previously have successful ' .
                   'request with same scopes');
        $this->route->complete($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code',
            [P::MINECRAFT_SERVER_SESSION]
        ));
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    public function testAcceptRequiredOnNewScope(FunctionalTester $I) {
        $I->amAuthenticated();
        $I->wantTo('get accept_required if I have previous successful request, but now require some new scope');
        $this->route->complete($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code',
            [P::MINECRAFT_SERVER_SESSION]
        ), ['accept' => true]);
        $this->route->complete($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code',
            [P::MINECRAFT_SERVER_SESSION, 'account_info']
        ));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'accept_required',
            'parameter' => '',
            'statusCode' => 401,
        ]);
    }

    public function testCompleteActionWithDismissState(FunctionalTester $I) {
        $I->amAuthenticated();
        $I->wantTo('get access_denied error if I pass accept in false state');
        $this->route->complete($this->buildQueryParams(
            'ely',
            'http://ely.by',
            'code',
            [P::MINECRAFT_SERVER_SESSION]
        ), ['accept' => false]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'access_denied',
            'parameter' => '',
            'statusCode' => 401,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    private function buildQueryParams(
        $clientId = null,
        $redirectUri = null,
        $responseType = null,
        $scopes = [],
        $state = null,
        $customData = []
    ) {
        $params = $customData;
        if ($clientId !== null) {
            $params['client_id'] = $clientId;
        }

        if ($redirectUri !== null) {
            $params['redirect_uri'] = $redirectUri;
        }

        if ($responseType !== null) {
            $params['response_type'] = $responseType;
        }

        if ($state !== null) {
            $params['state'] = $state;
        }

        if (!empty($scopes)) {
            if (is_array($scopes)) {
                $scopes = implode(',', $scopes);
            }

            $params['scope'] = $scopes;
        }

        return $params;
    }

    private function testOauthParamsValidation(FunctionalTester $I, $action) {
        $I->wantTo('check behavior on invalid request without one or few params');
        $this->route->$action($this->buildQueryParams());
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_request',
            'parameter' => 'client_id',
            'statusCode' => 400,
        ]);

        $I->wantTo('check behavior on invalid client id');
        $this->route->$action($this->buildQueryParams('non-exists-client', 'http://some-resource.by', 'code'));
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_client',
            'statusCode' => 401,
        ]);

        $I->wantTo('check behavior on invalid response type');
        $this->route->$action($this->buildQueryParams('ely', 'http://ely.by', 'kitty'));
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'unsupported_response_type',
            'parameter' => 'kitty',
            'statusCode' => 400,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');

        $I->wantTo('check behavior on some invalid scopes');
        $this->route->$action($this->buildQueryParams('ely', 'http://ely.by', 'code', [
            P::MINECRAFT_SERVER_SESSION,
            'some_wrong_scope',
        ]));
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_scope',
            'parameter' => 'some_wrong_scope',
            'statusCode' => 400,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');

        $I->wantTo('check behavior on request internal scope');
        $this->route->$action($this->buildQueryParams('ely', 'http://ely.by', 'code', [
            P::MINECRAFT_SERVER_SESSION,
            P::BLOCK_ACCOUNT,
        ]));
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'error' => 'invalid_scope',
            'parameter' => P::BLOCK_ACCOUNT,
            'statusCode' => 400,
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

}